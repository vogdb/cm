<?php

function smarty_function_viewTemplate(array $params, Smarty_Internal_Template $template) {
    /** @var CM_Frontend_Render $render */
    $render = $template->smarty->getTemplateVars('render');
    $viewResponse = $render->getFrontend()->getClosestViewResponse('CM_View_Abstract');

    $tplName = (string) $params['file'];
    unset($params['file']);
    return $render->fetchViewTemplate($viewResponse->getView(), $tplName, $params);
}
