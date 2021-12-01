<?php
/*
 * Parse docx files
 */
function smpl_docxparser($p){
	$out = '';
	if(function_exists("zip_open")){
		if(class_exists('XMLReader')){
			//unzip the file
			$corexml = new Xml2Assoc();
			$a = @($corexml->parseFile('zip://'.$p.'#docProps/core.xml'));
			$x = $a['cp:coreProperties'][0];

			$appxml  = new Xml2Assoc();
			$a = @( $appxml->parseFile('zip://'.$p.'#docProps/app.xml'));
			$y = $a['Properties'][0];
			$out .= '<table class="smpl_exif_table">';
			if(!isset($x)){
				$x['dc:creator'][0]          = t('unknown');
				$x['cp:lastModifiedBy'][0]   = t('unknown');
				$x['dcterms:created'][0][0]  = t('unknown');
				$x['dcterms:modified'][0][0] = t('unknown');
				$x['cp:revision'][0]         = t('unknown');
				$x['cp:lastPrinted'][0]      = t('unknown');
			}
			$out .= smpl_fx( t('Author')       ,$x['dc:creator'][0] );
			$out .= smpl_fx( t('Last Modify')  ,$x['cp:lastModifiedBy'][0] );
			$out .= smpl_fx( t('Created')      ,$x['dcterms:created'][0][0] );
			$out .= smpl_fx( t('Last Modified'),$x['dcterms:modified'][0][0] );
			$out .= smpl_fx( t('Revision')     ,$x['cp:revision'][0] );
			$out .= smpl_fx( t('Last printed') ,$x['cp:lastPrinted'][0]);
			
			if(!isset($y)){
				$y['Application'][0] = t('unknown');
				$y['AppVersion'][0]  = t('unknown');
				$y['Pages'][0]       = t('unknown');
				$y['Words'][0]       = t('unknown');
				$y['Characters'][0]  = t('unknown');
				$y['TotalTime'][0]   = t('unknown');
			}
				
			$out .= smpl_fx( t('Applicaton')   ,$y['Application'][0]);
			$out .= smpl_fx( t('App. version') ,$y['AppVersion'][0]);
			$out .= smpl_fx( t('Pages')        ,$y['Pages'][0] );
			$out .= smpl_fx( t('Words')        ,$y['Words'][0] );
			$out .= smpl_fx( t('Characters')   ,$y['Characters'][0]);
			$out .= smpl_fx( t('Total times')  ,$y['TotalTime'][0],'min' );
			$out .='</table>';
		}else{
			$out = 'Sorry! There is not installed XMLReader in PHP! I can not open XML files.';
		}
	}else{
		$out = t("Sorry! There is not ZIP library in PHP! I can not open the docx files!");
	}
	return $out;
}


/**
 * XML2Assoc Class to creating
 * PHP Assoc Array from XML File
 *
 * @author godseth (AT) o2.pl & rein_baarsma33 (AT) hotmail.com (Bugfixes in parseXml Method)
 * @uses XMLReader
 *
 */

class Xml2Assoc {

    /**
     * Optimization Enabled / Disabled
     *
     * @var bool
     */
    protected $bOptimize = false;

    /**
     * Method for loading XML Data from String
     *
     * @param string $sXml
     * @param bool $bOptimize
     */

    public function parseString( $sXml , $bOptimize = false) {
        $oXml = new XMLReader();
        $this -> bOptimize = (bool) $bOptimize;
        try {

            // Set String Containing XML data
            $oXml->XML($sXml);

            // Parse Xml and return result
            return $this->parseXml($oXml);

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Method for loading Xml Data from file
     *
     * @param string $sXmlFilePath
     * @param bool $bOptimize
     */
    public function parseFile( $sXmlFilePath , $bOptimize = false ) {
        $oXml = new XMLReader();
        $this -> bOptimize = (bool) $bOptimize;
        try {
            // Open XML file
            $oXml->open($sXmlFilePath);

            // // Parse Xml and return result
            return $this->parseXml($oXml);

        } catch (Exception $e) {
            echo $e->getMessage(). ' | Try open file: '.$sXmlFilePath;
        }
    }

    /**
     * XML Parser
     *
     * @param XMLReader $oXml
     * @return array
     */
    protected function parseXml( XMLReader $oXml ) {

        $aAssocXML = null;
        $iDc = -1;

        while($oXml->read()){
            switch ($oXml->nodeType) {

                case XMLReader::END_ELEMENT:

                    if ($this->bOptimize) {
                        $this->optXml($aAssocXML);
                    }
                    return $aAssocXML;

                case XMLReader::ELEMENT:

                    if(!isset($aAssocXML[$oXml->name])) {
                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name] = '';
                            } else {
                                $aAssocXML[$oXml->name] = $this->parseXML($oXml);
                            }
                        }
                    } elseif (is_array($aAssocXML[$oXml->name])) {
                        if (!isset($aAssocXML[$oXml->name][0]))
                        {
                            $temp = $aAssocXML[$oXml->name];
                            foreach ($temp as $sKey=>$sValue)
                            unset($aAssocXML[$oXml->name][$sKey]);
                            $aAssocXML[$oXml->name][] = $temp;
                        }

                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name][] = '';
                            } else {
                                $aAssocXML[$oXml->name][] = $this->parseXML($oXml);
                            }
                        }
                    } else {
                        $mOldVar = $aAssocXML[$oXml->name];
                        $aAssocXML[$oXml->name] = array($mOldVar);
                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name][] = '';
                            } else {
                                $aAssocXML[$oXml->name][] = $this->parseXML($oXml);
                            }
                        }
                    }

                    if($oXml->hasAttributes) {
                        $mElement =& $aAssocXML[$oXml->name][count($aAssocXML[$oXml->name]) - 1];
                        while($oXml->moveToNextAttribute()) {
                            $mElement[$oXml->name] = $oXml->value;
                        }
                    }
                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:

                    $aAssocXML[++$iDc] = $oXml->value;

            }
        }

        return $aAssocXML;
    }

    /**
     * Method to optimize assoc tree.
     * ( Deleting 0 index when element
     *  have one attribute / value )
     *
     * @param array $mData
     */
    public function optXml(&$mData) {
        if (is_array($mData)) {
            if (isset($mData[0]) && count($mData) == 1 ) {
                $mData = $mData[0];
                if (is_array($mData)) {
                    foreach ($mData as &$aSub) {
                        $this->optXml($aSub);
                    }
                }
            } else {
                foreach ($mData as &$aSub) {
                    $this->optXml($aSub);
                }
            }
        }
    }

}