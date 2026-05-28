<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    Written for or by ViaBill
 * @copyright Copyright (c) Viabill
 * @license   Addons PrestaShop license limitation
 *
 * @see       /LICENSE
 */

use ViaBill\Config\Config;
use ViaBill\Util\DebugLog;

require_once dirname(__FILE__) . '/../../vendor/autoload.php';

/**
 * ViaBill Custom Code Controller Class.
 * Allows administrators to inject custom CSS and JavaScript code.
 *
 * Class AdminViaBillCustomCodeController
 */
class AdminViaBillCustomCodeController extends ModuleAdminController
{
    /**
     * @var ViaBill
     */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        parent::__construct();
    }

    /**
     * Initialize the controller.
     */
    public function init()
    {
        if (isset($this->context->cookie->saveSuccessMessage)) {
            $this->confirmations[] = $this->context->cookie->saveSuccessMessage;
            unset($this->context->cookie->saveSuccessMessage);
        }

        parent::init();
    }

    /**
     * Initialize content.
     */
    public function initContent()
    {
        $this->content = $this->renderView() . $this->renderForm();

        return parent::initContent();
    }

    /**
     * Process form submission.
     *
     * @return bool|ObjectModel|void
     */
    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('submitViaBillCustomCode')) {
            $customCSS = (string) Tools::getValue('VIABILL_CUSTOM_CSS');
            $customJS = (string) Tools::getValue('VIABILL_CUSTOM_JS');

            Configuration::updateValue('VIABILL_CUSTOM_CSS', $customCSS);
            Configuration::updateValue('VIABILL_CUSTOM_JS', $customJS);

            $this->context->cookie->saveSuccessMessage = $this->trans(
                'Custom code settings have been successfully updated.',
                [],
                'Modules.Viabill.Admin'
            );

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminViaBillCustomCode'));
        }
    }

    /**
     * Add CSS and JS files to the controller.
     *
     * @param bool $isNewTheme
     *
     * @throws Exception
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        if (method_exists($this->context->controller, 'addJquery')) {
            $this->context->controller->addJquery();
        }

        $cssPath = $this->module->getLocalPath() . 'views/css/admin/custom-code.css';
        $jsPath = $this->module->getLocalPath() . 'views/js/admin/custom-code.js';

        if (file_exists($cssPath)) {
            $this->addCSS($this->module->getPathUri() . 'views/css/admin/custom-code.css');
        }

        if (file_exists($jsPath)) {
            $this->addJS($this->module->getPathUri() . 'views/js/admin/custom-code.js');
        }
    }

    /**
     * Render the configuration form.
     *
     * @return string
     */
    public function renderForm()
{
    $action = $this->context->link->getAdminLink('AdminViaBillCustomCode');
    $customCSS = Tools::safeOutput((string) Configuration::get('VIABILL_CUSTOM_CSS'));
    $customJS = Tools::safeOutput((string) Configuration::get('VIABILL_CUSTOM_JS'));

    $html = '
    <form method="post" action="' . $action . '" style="margin-top: 20px;">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-code"></i> ' . $this->trans('Custom CSS and JavaScript Settings', [], 'Modules.Viabill.Admin') . '
            </div>

            <div class="panel-body">
                <p style="margin-bottom: 20px;">' . $this->trans(
                    'Add custom CSS and JavaScript code to customize your ViaBill integration. This code will be injected into all front-end pages.',
                    [],
                    'Modules.Viabill.Admin'
                ) . '</p>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label for="VIABILL_CUSTOM_CSS" style="display:block; font-weight:600; margin-bottom:10px;">
                        ' . $this->trans('Custom CSS Code', [], 'Modules.Viabill.Admin') . '
                    </label>
                    <textarea
                        id="VIABILL_CUSTOM_CSS"
                        name="VIABILL_CUSTOM_CSS"
                        rows="14"
                        class="form-control viabill-code-editor"
                        style="width:100%; min-height:320px; font-family:monospace;"
                    >' . $customCSS . '</textarea>
                    <p class="help-block" style="margin-top:8px;">
                        ' . $this->trans(
                            'Enter your custom CSS code here (without &lt;style&gt; tags). It will be inserted in the page header.',
                            [],
                            'Modules.Viabill.Admin'
                        ) . '
                    </p>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="VIABILL_CUSTOM_JS" style="display:block; font-weight:600; margin-bottom:10px;">
                        ' . $this->trans('Custom JavaScript Code', [], 'Modules.Viabill.Admin') . '
                    </label>
                    <textarea
                        id="VIABILL_CUSTOM_JS"
                        name="VIABILL_CUSTOM_JS"
                        rows="14"
                        class="form-control viabill-code-editor"
                        style="width:100%; min-height:320px; font-family:monospace;"
                    >' . $customJS . '</textarea>
                    <p class="help-block" style="margin-top:8px;">
                        ' . $this->trans(
                            'Enter your custom JavaScript code here (without &lt;script&gt; tags). It will be inserted in the page footer.',
                            [],
                            'Modules.Viabill.Admin'
                        ) . '
                    </p>
                </div>
            </div>

            <div class="panel-footer" style="display:block; overflow:auto;">
                <button type="submit" name="submitViaBillCustomCode" class="btn btn-primary pull-right">
                    <i class="process-icon-save"></i> ' . $this->trans('Save', [], 'Modules.Viabill.Admin') . '
                </button>
            </div>
        </div>
    </form>';

    return $html;
}

    /**
     * Render the page content.
     *
     * @return string
     */
    public function renderView()
    {
        $infoHtml = '
        <div class="alert alert-info">
            <h4><i class="icon-info-circle"></i> ' . $this->trans('Custom CSS and JavaScript Code', [], 'Modules.Viabill.Admin') . '</h4>
            <p>' . $this->trans('This feature allows you to add custom CSS and JavaScript code to enhance your ViaBill integration without modifying theme files.', [], 'Modules.Viabill.Admin') . '</p>
            <ul>
                <li>' . $this->trans('Customize the appearance of ViaBill price tags', [], 'Modules.Viabill.Admin') . '</li>
                <li>' . $this->trans('Add custom styling to payment buttons', [], 'Modules.Viabill.Admin') . '</li>
                <li>' . $this->trans('Integrate third-party analytics or tracking scripts', [], 'Modules.Viabill.Admin') . '</li>
                <li>' . $this->trans('Implement custom behavior for the checkout process', [], 'Modules.Viabill.Admin') . '</li>
            </ul>
        </div>

        <div class="alert alert-warning">
            <h4><i class="icon-warning"></i> ' . $this->trans('Important Notes', [], 'Modules.Viabill.Admin') . '</h4>
            <p>' . $this->trans('Do NOT include &lt;style&gt; or &lt;script&gt; tags in your code - they will be added automatically.', [], 'Modules.Viabill.Admin') . '</p>
            <p>' . $this->trans('Always test your custom code thoroughly before deploying to production.', [], 'Modules.Viabill.Admin') . '</p>
            <p>' . $this->trans('Invalid code may break your website functionality.', [], 'Modules.Viabill.Admin') . '</p>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-file-code-o"></i> ' . $this->trans('Code Examples', [], 'Modules.Viabill.Admin') . '
            </div>
            <div class="panel-body">
                <h4>' . $this->trans('Example 1: Customize ViaBill Price Tag Colors', [], 'Modules.Viabill.Admin') . '</h4>
                <p>' . $this->trans('Add this to the Custom CSS Code field:', [], 'Modules.Viabill.Admin') . '</p>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">/* Customize ViaBill price tag appearance */
.viabill-pricetag {
    color: #9b26b7 !important;
    font-weight: bold;
}

.viabill-pricetag a {
    color: #25b9d7 !important;
    text-decoration: underline;
}</pre>

                <h4>' . $this->trans('Example 2: Track ViaBill Payment Selection', [], 'Modules.Viabill.Admin') . '</h4>
                <p>' . $this->trans('Add this to the Custom JavaScript Code field:', [], 'Modules.Viabill.Admin') . '</p>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">// Track when ViaBill payment method is selected
$(document).ready(function() {
    $(\'input[name="payment-option"]\').on(\'change\', function() {
        if ($(this).data(\'module-name\') === \'viabill\') {
            console.log(\'ViaBill payment method selected\');
            // Add your tracking code here
        }
    });
});</pre>
            </div>
        </div>';

        return $infoHtml;
    }

    /*
    Legacy wrapper for translation l method
    */
    public function l($string, $specific = false, $locale = null)
    {
        return $this->trans($string, [], 'Modules.Viabill.Admin', $locale);
    }
}