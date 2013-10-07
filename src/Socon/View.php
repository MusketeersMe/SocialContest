<?php
namespace Socon;

use Phlyty\View\ViewInterface;

/**
 * Zend view -- proxies to zend view
 *
 */
class View implements ViewInterface
{
    protected $templateDir;

    protected $viewModel;

    protected $helper;

    /**
     * @param mixed $templateDir
     */
    public function setTemplateDir($templateDir)
    {
        $this->templateDir = $templateDir;
    }

    /**
     * @return mixed
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }

    /**
     * @param $helper
     */
    public function setHelper($helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     */
    public function __call($method, $params) {
        if (isset($this->helper)) {
            return call_user_func_array([$this->helper, $method], $params);
        }
    }

    /**
     * @return mixed
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Render a template
     *
     * Proxies to parent object, but provides defaults for $viewModel and
     * $partials.
     *
     * @param  string $template  Either a template string or a template file in the template path
     * @param  mixed  $viewModel An array or object with items to inject in the template
     * @return string
     */
    public function render($template, $viewModel = [])
    {
        // save in case header/footer need values
        $this->viewModel = $viewModel;

        // make view model variables readily available in scope
        extract((array) $viewModel, EXTR_SKIP);

        // get the output
        ob_start();
            // render include the $template
            include($this->getTemplateDir() . DIRECTORY_SEPARATOR . $template);
        return ob_get_clean();
    }

    /**
     * Render a partial template
     *
     * Does not inherit or mess with parent viewModels
     *
     * @param  string $template  Either a template string or a template file in the template path
     * @param  mixed  $viewModel An array or object with items to inject in the template
     * @return string
     */
    public function partial($template, $viewModel = [])
    {
        // make view model variables readily available in scope
        extract((array) $viewModel, EXTR_SKIP);

        // get the output
        ob_start();
            // render include the $template
            include($this->getTemplateDir() . DIRECTORY_SEPARATOR . $template);
        return ob_get_clean();
    }

    public function header()
    {
        // make view model variables readily available in scope
        extract((array) $this->viewModel, EXTR_SKIP);
        include($this->getTemplateDir() . DIRECTORY_SEPARATOR . '__header.phtml');
    }

    public function footer()
    {
        // make view model variables readily available in scope
        extract((array) $this->viewModel, EXTR_SKIP);
        include($this->getTemplateDir() . DIRECTORY_SEPARATOR . '__footer.phtml');
    }
}
