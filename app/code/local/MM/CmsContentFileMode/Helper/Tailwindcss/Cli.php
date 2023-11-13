<?php
// TODO move to shell utility
class MM_CmsContentFileMode_Helper_Tailwindcss_Cli extends Mage_Core_Helper_Abstract
{
    const DEBUG = false;
    public $cmdOptions = [];
    public $output;
    public $return;

    public function run() {
        $tailwindCli =  "cd " . Mage::getBaseDir('lib') . DS . "tailwindcss; ./tailwindcss";
        $cmd =  sprintf(
            '%s %s %s 2>&1',
            $tailwindCli,
            $this->getCmdOptions(),
            !self::DEBUG ? "--minify" : ""
        );
        if (self::DEBUG) {
            $this->getHelper()->getSessionMessage()->addWarning($cmd);
        }
        exec($cmd, $this->output, $this->return);
        if ($this->return !== 0) {
			Mage::helper('mm_cmscontentfilemode')->getSessionMessage()->addError(
                sprintf("Problem compiling tailwindcss, commad was %s check chmod +x permission for ./lib/tailwindcss/tailwindcss", $cmd)
            );
			Mage::logException(new Exception( $this->output[0] ." [..truncate..] \n\n"));
		}
        return $this->return === 0;
    }

    public function setContent($value) {
        $this->cmdOptions['--content'] = $value;
        return $this;
    }
    public function setInput($value) {
        $this->cmdOptions['--input'] = $value;
        return $this;
    }
    public function setOutput($value) {
        $this->cmdOptions['--output'] = $value;
        return $this;
    }
    public function setConfig($value) {
        $this->cmdOptions['--config'] = $value;
        return $this;
    }

    public function getOutput() {
        return $this->output;
    }

    public function getReturn() {
        return $this->return;
    }
    
    /**
     * @return MM_CmsContentFileMode_Helper_Data
     */
    protected function getHelper() {
        return Mage::helper('mm_cmscontentfilemode');
    }

    private function getCmdOptions() {
        return implode(" ", array_map(function($key, $value) {
            return sprintf("%s '%s'", $key, $value);
        }, array_keys($this->cmdOptions), $this->cmdOptions));
    }
}
