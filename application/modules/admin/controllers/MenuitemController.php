<?php
/**
 * @package   Soulex
 * @copyright Copyright (C) 2010 - Present, miholeus
 * @author    miholeus <me@miholeus.com> {@link http://miholeus.com}
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version    $Id: $
 */

/**
 * MenuitemController processes requests to menu items
 *
 * @author miholeus
 */
class Admin_MenuitemController extends Soulex_Controller_Abstract
{
    /**
     * Show menu items including different search criteria
     * 
     * @return void
     */
    public function indexAction()
    {
        $mdlMenuItem = new Admin_Model_MenuItem();

        if($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if(isset($post['cid'])) {
                if(is_array($post['cid'])
                        && count($post['cid']) == $post['boxchecked']) {
                    $mdlMenuItem->delete($post['cid']);
                    return $this->_redirect('/admin/menuitem');
                } else {
                    throw new Exception('FCS  is not correct! Wrong request!');
                }
            }
        }

        $limit = $this->_getParam('limit', 20);
        $adapter = $mdlMenuItem->fetchPaginator(null, array('lft', 'label'));

        $paginator = new Zend_Paginator($adapter);
        // show items per page
        if($limit != 0) {
            $paginator->setItemCountPerPage($limit);
        } else {
            $paginator->setItemCountPerPage(-1);
        }

        $page = $this->_request->getParam('page', 1);
        $paginator->setCurrentPageNumber($page);
        // pass the paginator to the view to render
        $this->view->paginator = $paginator;
        Zend_Registry::set('pagination_limit', $limit);
        
        $maxMenuLevel = $mdlMenuItem->findMaxMenuLevel();
        $this->view->menuLevels = range(1, $maxMenuLevel);

        $mdlMenu = new Admin_Model_Menu();
        $this->view->menus = $mdlMenu->fetchAll();

        $this->view->render('menuitem/index.phtml');
    }
    /**
     * Show menu item form and create a new item while postback
     * 
     * @return void
     */
    public function createAction()
    {
        $frmMenuItem = new Admin_Form_MenuItem();
        $mdlMenu = new Admin_Model_Menu();
        $menus = $mdlMenu->fetchAll();
        foreach($menus as $menu) {
            $frmMenuItem->addElementOption('menuId', $menu->getId(), $menu->getTitle());
        }

        $mdlMenuItem = new Admin_Model_MenuItem();
        $items = $mdlMenuItem->fetchAllGrouppedByParentId();
        $mdlMenuItem->processTreeElementForm($items, $frmMenuItem, 'parentId');

        if($this->_request->isPost() &&
                $frmMenuItem->isValid($this->_request->getPost())) {
                $frmMenuItem->removeElement('id');
                $mdlMenuItem = new Admin_Model_MenuItem($frmMenuItem->getValues());
                $mdlMenuItem->save();
                return $this->_redirect('/admin/menuitem');
        }


        $this->view->form = $frmMenuItem;
        $this->renderSubmenu(false);
        $this->view->render('menuitem/create.phtml');
    }
    /**
     * Show menu item form for editing info and update menu item
     * 
     * @return void
     */
    public function editAction()
    {
        $id = $this->_getParam('id');
        $frmMenuItem = new Admin_Form_MenuItem();
        $mdlMenu = new Admin_Model_Menu();
        $menus = $mdlMenu->fetchAll();
        foreach($menus as $menu) {
            $frmMenuItem->addElementOption('menuId', $menu->getId(), $menu->getTitle());
        }

        $mdlMenuItem = new Admin_Model_MenuItem();
        $menuItem = $mdlMenuItem->find($id);
        $items = $mdlMenuItem->fetchAllGrouppedByParentId();
        $mdlMenuItem->processTreeElementForm($items, $frmMenuItem, 'parentId',
                $menuItem->getParentId());
        $frmMenuItem->getElement('parentId')->removeMultiOption($id);

        if($this->_request->isPost() &&
                $frmMenuItem->isValid($this->_request->getPost())) {
            $mdlMenuItem = new Admin_Model_MenuItem($frmMenuItem->getValues());
            $mdlMenuItem->save();

            return $this->_redirect('/admin/menuitem');
        }

        $frmMenuItem->populate(array(
            'id' => $menuItem->getId(),
            'label' => $menuItem->getLabel(),
            'uri' => $menuItem->getUri(),
            'menuId' => $menuItem->getMenuId(),
            'position' => $menuItem->getPosition(),
            'published' => $menuItem->getPublished()
        ));

        $this->view->form = $frmMenuItem;
        $this->renderSubmenu(false);
        $this->view->render('menuitem/edit.phtml');
    }
    /**
     * Delete menu item by its id
     */
    public function deleteAction()
    {
        $id = $this->_getParam('id');
        $mdlMenuItem = new Admin_Model_MenuItem();
        $mdlMenuItem->delete($id);
        $this->_redirect('/admin/menuitem');
    }
}
