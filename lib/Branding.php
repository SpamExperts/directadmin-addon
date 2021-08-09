<?php

class Branding {

    protected $brandName;
    protected $pluginConf;
    protected $files;
    private $maxLogoWidth;
    private $maxLogoHeight;

    public function __construct() {
        $this->pluginConf = SE_BASE_DIR . DS . 'plugin.conf';
        $this->files = array(
            SE_BASE_DIR . DS . 'hooks' . DS . 'user_txt.html',
            SE_BASE_DIR . DS . 'hooks' . DS . 'admin_txt.html',
            SE_BASE_DIR . DS . 'hooks' . DS . 'reseller_txt.html'
        );
        $this->maxLogoHeight = 150;
        $this->maxLogoWidth = 600;
        $this->setBrandNameFromFile();
    }

    public function getBrandName() {
        return $this->brandName;
    }

    public function setBrandNameFromFile() {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
        $branding = parse_ini_string($this->getBrandFileContent($this->pluginConf), true);
        $this->brandName = $branding['name'];
    }

    public function setBrandName($brandName) {
        $length = strlen(utf8_decode(html_entity_decode($brandName, ENT_COMPAT, 'utf-8')));
        if ($length < 4) {
            return array('status' => 'failed',
                'msg' => 'Brandame must consist of at least 4 characters.');
        }
        if ($length > 32) {
            return array('status' => 'failed',
                'msg' => 'Maximum length of brandname allowed is 32 characters.');
        }
        if (preg_match('/[\x00-\x1F\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7F]/', $brandName)) {
            return array('status' => 'failed',
                'msg' => 'Brandame must be alphanumeric. Additionally white space can be used');
        }
        $failedTo = array();
        $output = array();
        $result = $this->changeBrandingInConf($brandName);
        if (!empty($result)) {
            $failedTo[] = array('file' => $this->pluginConf,
                'reason' => $result);
        }
        foreach ($this->files as $file) {
            $data = $this->getBrandFileContent($file);
            $replaced = str_replace($this->brandName, $brandName, $data);
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
            exec(SE_BASE_DIR . DS . 'scripts' . DS . 'getconfig --brandsave ' . $file . ' "' . $replaced . '" 2>&1', $output);
            unset($data);
            unset($replaced);
            if (!empty($output)) {
                $failedTo[] = array('file' => $file,
                    'reason' => $output);
            }
            unset($output);
        }
        if (!empty($failedTo)) {
            return array('status' => 'failed',
                'msg' => 'Branding changing failure!');
        }
        return array('status' => 'success',
            'msg' => 'Branding changed successfully!');
    }

    private function getBrandFileContent($fileName) {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        exec(SE_BASE_DIR . DS . 'scripts' . DS . 'getconfig --brandget ' . $fileName, $content, $return);
        if (sizeof($content) > 1) {
            $data = implode("\n", $content);
        } else {
            $data = $content[0];
        }
        return $data;
    }

    private function changeBrandingInConf($brandName) {
        $output = array();
        $data = $this->getBrandFileContent($this->pluginConf);

        $replaced = str_replace('"' . $this->brandName . '"', '\"' . $brandName . '\"', $data);
        // If data isn't replaced for value in quotest then, Branding must be saved without quoutes (made for backward compatibility)
        if ($replaced == $data) {
            $replaced = str_replace($this->brandName, '\"' . $brandName . '\"', $data);
        }
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        exec(SE_BASE_DIR . DS . 'scripts' . DS . 'getconfig --brandsave ' . $this->pluginConf . ' "' . $replaced . '" 2>&1', $output);
        return $output;
    }

    public function setBrandLogo($filePath) {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        if (0 == shell_exec(SE_BASE_DIR . DS . 'scripts' . DS . 'getconfig --mvLogo ' . $filePath . ' ' . SE_BASE_DIR . DS . 'images' . DS . 'logo.jpg')) {
            return true;
        }
    }

    public function validateLogo($filePath) {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        exec(SE_BASE_DIR . DS . 'scripts' . DS . 'getconfig --chmodFile ' . $filePath, $output); // get permission to do that
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
        $imageInfo = getimagesize($filePath);
        if ($imageInfo['mime'] == "image/jpeg" && $imageInfo[0] <= $this->maxLogoWidth && $imageInfo[1] <= $this->maxLogoHeight) {
            return true;
        }
        false;
    }

    public function getMaxLogoWidth() {
        return $this->maxLogoWidth;
    }

    public function getMaxLogoHeight() {
        return $this->maxLogoHeight;
    }

}
