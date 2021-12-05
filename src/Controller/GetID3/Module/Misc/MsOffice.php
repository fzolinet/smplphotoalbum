<?php

namespace Drupal\smplphotoalbum\Controller\GetID3\Module\Misc;

use Drupal\smplphotoalbum\Controller\GetID3\Module\Handler;
use Drupal\smplphotoalbum\Controller\GetID3\Utils;

/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at https://github.com/JamesHeinrich/getID3       //
//            or https://www.getid3.org                        //
//            or http://getid3.sourceforge.net                 //
//  see readme.txt for more details                            //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.misc.msoffice.php                                    //
// module for analyzing MS Office (.doc, .xls, etc) files      //
//                                                            ///
/////////////////////////////////////////////////////////////////

class MsOffice extends Handler
{
	/**
	 * @return bool
	 */
	public function Analyze() {
		$info = &$this->getid3->info;

		$this->fseek($info['avdataoffset']);
		$DOCFILEheader = $this->fread(8);
		$magic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
		if (substr($DOCFILEheader, 0, 8) != $magic) {
			$this->error('Expecting "'.Utils::PrintHexBytes($magic).'" at '.$info['avdataoffset'].', found '.Utils::PrintHexBytes(substr($DOCFILEheader, 0, 8)).' instead.');
			return false;
		}
		$info['fileformat'] = 'msoffice';

		$this->error('MS Office (.doc, .xls, etc) parsing not enabled in this version of getID3() ['.$this->getid3->version().']');
		return false;

	}

}