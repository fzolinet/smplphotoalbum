<?php

class AudioFile
{
	var $wave_id;
	var $wave_type;
	var $wave_compression;
	var $wave_channels;
	var $wave_framerate;
	var $wave_byterate;
	var $wave_bits;
	var $wave_size;
	var $wave_filename;
	var $wave_length;

	var $id3_tag;
	var $id3_title;
	var $id3_artist;
	var $id3_album;
	var $id3_year;
	var $id3_comment;
	var $id3_genre;

	var $id3v2info;

	var $visual_graph_color;	// HTML-Style: "#rrggbb"
	var $visual_background_color;
	var $visual_grid_color;
	var $visual_border_color;
	var $visual_grid;		// true/false
	var $visual_border;		// true/false
	var $visual_width;		// width in pixel
	var $visual_height;		// height in pixel
	var $visual_graph_mode;		// 0|1
	var $visual_fileformat;		// "jpeg","png", everything & else default = "png"

	// ************************************************************************
	// mp3info extracts the attributes of mp3-files
	// (code contributed by reto gassmann (gassi@gassi.cx)
	// ************************************************************************

	function mp3info()
	{
		$byte 			= array();
		$version 		= array("MPEG Version 2.5",false,"MPEG Version 2 (ISO/IEC 13818-3)","MPEG Version 1 (ISO/IEC 11172-3)");
		$version_bitrate	= array(1,false,1,0);
		$version_sampling	= array(2,false,1,0);
		$layer			= array(false,"Layer III","Layer II","Layer I");
		$layer_bitrate		= array(false,2,1,0);
		$layer_lengt		= array(false,1,1,0);
		$protection 		= array("Protected by CRC (16bit crc follows header)","Not protected");
		$byterate		= array(
				array(
						array("free",32,64,96,128,160,192,224,256,288,320,352,384,416,448,"bad"),
						array("free",32,48,56, 64, 80, 96,112,128,160,192,224,256,320,384,"bad"),
						array("free",32,40,48, 56, 64, 80, 96,112,128,160,192,224,256,320,"bad")
				),
				array(
						array("free",32,48,56, 64, 80, 96,112,128,144,160,176,192,224,256,"bad"),
						array("free", 8,16,24, 32, 40, 48, 56, 64, 80, 96,112,128,144,160,"bad"),
						array("free", 8,16,24, 32, 40, 48, 56, 64, 80, 96,112,128,144,160,"bad")
				)
		);
		$samplingrate		= array(
				array(44100,48000,32000,false),
				array(22050,24000,16000,false),
				array(11025,12000, 8000,false)
		);
		$cannel_mode	= array("Stereo","Joint stereo (Stereo)","Dual channel (Stereo)","Single channel (Mono)");
		$copyright	= array("Audio is not copyrighted","Audio is copyrighted ");
		$original	= array("Copy of original media","Original media");
		$emphasis	= array("none","50/15 ms",false,"CCIT J.17 ");

		//id3-stuff

		$genre			= array
		("Blues","Classic Rock","Country","Dance","Disco","Funk","Grunge","Hip-Hop","Jazz","Metal","New Age","Oldies","Other","Pop","R&B",
				"Rap","Reggae","Rock","Techno","Industrial","Alternative","Ska","Death Metal","Pranks","Soundtrack","Euro-Techno","Ambient","Trip-Hop",
				"Vocal","Jazz+Funk","Fusion","Trance","Classical","Instrumental","Acid","House","Game","Sound Clip","Gospel","Noise","Alternative Rock",
				"Bass","Soul","Punk","Space","Meditative","Instrumental Pop","Instrumental Rock","Ethnic","Gothic","Darkwave","Techno-Industrial",
				"Electronic","Pop-Folk","Eurodance","Dream","Southern Rock","Comedy","Cult","Gangsta","Top 40","Christian Rap","Pop/Funk","Jungle",
				"Native US","Cabaret","New Wave","Psychadelic","Rave","Showtunes","Trailer","Lo-Fi","Tribal","Acid Punk","Acid Jazz","Polka","Retro",
				"Musical","Rock & Roll","Hard Rock","Folk","Folk-Rock","National Folk","Swing","Fast Fusion","Bebob","Latin","Revival","Celtic","Bluegrass",
				"Avantgarde","Gothic Rock","Progressive Rock","Psychedelic Rock","Symphonic Rock","Slow Rock","Big Band","Chorus","Easy Listening","Acoustic",
				"Humour","Speech","Chanson","Opera","Chamber Music","Sonata","Symphony","Booty Bass","Primus","Porn Groove","Satire","Slow Jam","Club",
				"Tango","Samba","Folklore","Ballad","Power Ballad","Rhytmic Soul","Freestyle","Duet","Punk Rock","Drum Solo","Acapella","Euro-House",
				"Dance Hall","Goa","Drum & Bass","Club-House","Hardcore","Terror","Indie","BritPop","Negerpunk","Polsk Punk","Beat","Christian Gangsta Rap",
				"Heavy Metal","Black Metal","Crossover","Contemporary Christian","Christian Rock","Merengue","Salsa","Trash Metal","Anime","Jpop","Synthpop");

		//id3v2 check----------------------------
		$footer = 0;
		$header = 0;
		$v1tag	= 0;
		$fp = fopen($this->wave_filename,"r");
		$tmp = fread($fp,3);
		if($tmp == "ID3")
		{
			// id3v2 tag is present
			$this->getId3v2($fp);

			// getId3v2 will position pointer at end of header
			$header= ftell($fp);

		} else {
			fseek ($fp,0);
			$this->id3v2 = false;
		}

		for ($x=0;$x<4;$x++)
		{
			$byte[$x] = ord(fread($fp,1));
		}
		fseek ($fp, -128 ,SEEK_END);
		$TAG = fread($fp,128);
		fclose($fp);

		//id tag?-------------------------------

		if(substr($TAG,0,3) == "TAG")
		{
			$v1tag = 128;
			$info["mpeg_id3v1_tag"]["title"] 	= rtrim(substr($TAG,3,30));
			$info["mpeg_id3v1_tag"]["artist"] 	= rtrim(substr($TAG,33,30));
			$info["mpeg_id3v1_tag"]["album"] 	= rtrim(substr($TAG,63,30));
			$info["mpeg_id3v1_tag"]["year"] 	= rtrim(substr($TAG,93,4));
			$info["mpeg_id3v1_tag"]["comment"] 	= rtrim(substr($TAG,97,30));
			$info["mpeg_id3v1_tag"]["genre"]	= "";
			$tmp = ord(substr($TAG,127,1));
			if($tmp < count($genre))
			{
				$info["mpeg_id3v1_tag"]["genre"] = $genre[$tmp];
			}
		} else {
			$info["mpeg_id3v1_tag"] = false;
		}

		//version-------------------------------

		$tmp = $byte[1] & 24;
		$tmp = $tmp >> 3;
		$info_i["mpeg_version"] = $tmp;
		$byte_v = $version_bitrate[$tmp];
		$byte_vs = $version_sampling[$tmp];
		$info["mpeg_version"] = $version[$tmp];

		//layer---------------------------------

		$tmp = $byte[1] & 6;
		$tmp = $tmp >> 1;
		$info_i["mpeg_layer"] = $tmp;
		$byte_l = $layer_bitrate[$tmp];
		$byte_len = $layer_lengt[$tmp];
		$info["mpeg_layer"] = $layer[$tmp];

		//bitrate-------------------------------

		$tmp = $byte[2] & 240;
		$tmp = $tmp >> 4;
		$info_i["mpeg_bitrate"] = $tmp;
		$info["mpeg_bitrate"] = $byterate[$byte_v][$byte_l][$tmp];

		//samplingrate--------------------------

		$tmp = $byte[2] & 12;
		$tmp = $tmp >> 2;
		$info["mpeg_sampling_rate"] = $samplingrate[$byte_vs][$tmp];

		//protection----------------------------

		$tmp = $byte[1] & 1;
		$info["mpeg_protection"] = $protection[$tmp];

		//paddingbit----------------------------

		$tmp = $byte[2] & 2;
		$tmp = $tmp >> 1;
		$byte_pad = $tmp;
		$info["mpeg_padding_bit"] = $tmp;

		//privatebit----------------------------

		$tmp = $byte[2] & 1;
		$byte_prv = $tmp;

		//channel_mode--------------------------

		$tmp = $byte[3] & 192;
		$tmp = $tmp >> 6;
		$info["mpeg_channel_mode"] = $cannel_mode[$tmp];

		//copyright-----------------------------

		$tmp = $byte[3] & 8;
		$tmp = $tmp >> 3;
		$info["mpeg_copyright"] = $copyright[$tmp];

		//original------------------------------

		$tmp = $byte[3] & 4;
		$tmp = $tmp >> 2;
		$info["mpeg_original"] = $original[$tmp];

		//emphasis------------------------------

		$tmp = $byte[3] & 3;
		$info["mpeg_emphasis"] = $emphasis[$tmp];

		//framelenght---------------------------

		if($info["mpeg_bitrate"] == 'free' or $info["mpeg_bitrate"] == 'bad' or
		!$info["mpeg_bitrate"] or !$info["mpeg_sampling_rate"])
		{
			$info["mpeg_framelength"] = 0;
		} else {
			if($byte_len == 0)
			{
				$rate_tmp = $info["mpeg_bitrate"] * 1000;
				$info["mpeg_framelength"] = (12 * $rate_tmp / $info["mpeg_sampling_rate"] + $byte_pad) * 4 ;
			} elseif($byte_len == 1) {
				$rate_tmp = $info["mpeg_bitrate"] * 1000;
				$info["mpeg_framelength"] = 144 * $rate_tmp / $info["mpeg_sampling_rate"] + $byte_pad;
			}
		}

		//duration------------------------------

		$tmp = filesize($this->wave_filename);
		$tmp = $tmp - $header - 4 - $v1tag;

		$tmp2 = 0;
		$info["mpeg_frames"]="";
		$info["mpeg_playtime"]="";
		if(!$info["mpeg_bitrate"] or $info["mpeg_bitrate"] == 'bad' or !$info["mpeg_sampling_rate"])
		{
			$info["mpeg_playtime"] = -1;
		} elseif($info["mpeg_bitrate"] == 'free')
		{
			$info["mpeg_playtime"] = -1;
		} else {
			$tmp2 = ((8 * $tmp) / 1000) / $info["mpeg_bitrate"];
			$info["mpeg_frames"] = floor($tmp/$info["mpeg_framelength"]);
			$tmp = $tmp * 8;
			if ($rate_tmp<>0)
			{
				$info["mpeg_playtime"] = $tmp/$rate_tmp;
			}
			$info["mpeg_playtime"] = $tmp2;
		}

		// transfer the extracted data into classAudioFile-structure

		$this->wave_id = "MPEG";
		$this->wave_type = $info["mpeg_version"];
		$this->wave_compression = $info["mpeg_layer"];
		$this->wave_channels = $info["mpeg_channel_mode"];
		$this->wave_framerate = $info["mpeg_sampling_rate"];
		$this->wave_byterate = $info["mpeg_bitrate"] . " Kbit/sec";
		$this->wave_bits = "n/a";
		$this->wave_size = filesize($this->wave_filename);
		$this->wave_length = $info["mpeg_playtime"];

		// pick up length from id3v2 tag if necessary and available
		if ($this->wave_length<1 && is_array($this->id3v2->TLEN) )
		{
			$this->wave_length= (  $this->id3v2->TLEN['value'] / 1000 );
		}

		$this->id3_tag = $info["mpeg_id3v1_tag"];

		if ($this->id3_tag)
		{
			$this->id3_title = $info["mpeg_id3v1_tag"]["title"];
			$this->id3_artist = $info["mpeg_id3v1_tag"]["artist"];
			$this->id3_album = $info["mpeg_id3v1_tag"]["album"];
			$this->id3_year = $info["mpeg_id3v1_tag"]["year"];
			$this->id3_comment = $info["mpeg_id3v1_tag"]["comment"];
			$this->id3_genre = $info["mpeg_id3v1_tag"]["genre"];
		}
	}

	// ************************************************************************
	// longCalc calculates the decimal value of 4 bytes
	// mode = 0 ... b1 is the byte with least value
	// mode = 1 ... b1 is the byte with most value
	// ************************************************************************

	function longCalc ($b1,$b2,$b3,$b4,$mode)
	{
		$b1 = hexdec(bin2hex($b1));
		$b2 = hexdec(bin2hex($b2));
		$b3 = hexdec(bin2hex($b3));
		$b4 = hexdec(bin2hex($b4));
		if ($mode == 0)
		{
			return ($b1 + ($b2*256) + ($b3 * 65536) + ($b4 * 16777216));
		} else {
			return ($b4 + ($b3*256) + ($b2 * 65536) + ($b1 * 16777216));
		}
	}

	// ************************************************************************
	// shortCalc calculates the decimal value of 2 bytes
	// mode = 0 ... b1 is the byte with least value
	// mode = 1 ... b1 is the byte with most value
	// ************************************************************************

	function shortCalc ($b1,$b2,$mode)
	{
		$b1 = hexdec(bin2hex($b1));
		$b2 = hexdec(bin2hex($b2));
		if ($mode == 0)
		{
			return ($b1 + ($b2*256));
		} else {
			return ($b2 + ($b1*256));
		}
	}

	// ************************************************************************
	// getCompression delivers a string which identifies the compression-mode
	// of the AudioFile-Object
	// ************************************************************************

	function getCompression ($id)
	{
		if ($this->wave_id!= "MPEG" && $this->wave_id!="OGG")
		{
			$append = "($id)";
			switch ($id)
			{
				case 0:  return ("unknown $append"); break;
				case 1:  return ("pcm/uncompressed $append"); break;
				case 2:  return ("microsoft adpcm $append"); break;
				case 6:  return ("itu g.711 a-law $append"); break;
				case 7:  return ("itu g.711 u-law $append"); break;
				case 17:   return ("ima adpcm $append"); break;
				case 20:   return ("itu g.723 adpcm (yamaha) $append"); break;
				case 49:   return ("gsm 6.10 $append"); break;
				case 64:   return ("itu g.721 adpcm $append"); break;
				case 80:   return ("mpeg $append"); break;
				case 65536:return ("experimental $append"); break;
				default:   return ("not defined $append"); break;
			}
		} else {
			return ($id);
		}
	}

	// ************************************************************************
	// getVisualization creates a graphical visualization of the audio-sample
	//                  (works ONLY * for uncompressed waves!
	//                              * files with 1 or 2 channels
	//                              * 8/16/24/32 bit sample-resolution )
	// ************************************************************************

	function getVisualization ($output)
	{
		$width=$this->visual_width;
		$height=$this->visual_height;
		$height_channel = $height / $this->wave_channels;
		if ($this->wave_filename<>"" && $this->wave_id == "RIFF" && $this->wave_type == "WAVE" && ($this->wave_channels>=1 && $this->wave_channels<=2) && $this->wave_bits%8==0)
		{
			$file = fopen ($this->wave_filename,"r");

			// read the first 12 bytes (RIFF- & WAVE-chunk)

			for ($i=0;$i<12;$i++)
			{
				$null = fgetc ($file);
			}

			// Read the next chunk-id, supposed to be "fmt "

			$chunk_id_3 = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
			if ($chunk_id_3 == "fmt ")
			{
				$chunk_size_3 = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
				for ($i=0;$i<$chunk_size_3;$i++)
				{
					$null = fgetc($file);
				}

				// Read the next chunk-id, supposed to be "data"
				$chunk_id_4 = "";
				while ($chunk_id_4 <> "data" && !feof($file))
				{
					$chunk_id_4 = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
					if ($chunk_id_4 <> "data")
					{
						$chunk_size_4 = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
						for ($i=0;$i<$chunk_size_4;$i++)
						{
							$null = fgetc($file);
						}
					}
				}
				if ($chunk_id_4 == "data")
				{
					$chunk_size_4 = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
					$visualData = array();
					$bytes_per_frame = ($this->wave_bits/8)*($this->wave_channels);
					$bytes_per_channel = ($this->wave_bits/8);
					$frames = $chunk_size_4 / $bytes_per_frame;
					$visual_frames = ceil($frames / $width);
					$frame_index = 1;
					$data_index = 1;

					// revised code -- computing bytes per pixel allows quick processing of large (>10MB) wavs by fseek()ing past unused data
					$bytes_per_pixel= floor($chunk_size_4/$width);
					$currentindex= 0;
					while (!feof($file)  && $currentindex < $chunk_size_4)
					{
						$loopindex= 0;
						for ($j=0;$j<$this->wave_channels;$j++)
						{
							$bytes = array();
							for ($i=0;$i<$bytes_per_channel;$i++)
							{
								$bytes[$i] = fgetc($file);
								$loopindex++;
							}
							switch ($bytes_per_channel)
							{
								case 1: $visualData[$j][$data_index]= $this->shortCalc($bytes[0],$bytes[1],0);
								break;
								case 2: $f=128;
								if (ord($bytes[1])&128) $f = 0;
								$x=chr((ord($bytes[1])&127) + $f);
								$visualData[$j][$data_index]= floor($this->shortCalc($bytes[0],$x,0)/256);
								break;
							}
							if (($j+1) == $this->wave_channels)
							{
								$data_index++;
							}
						}
						$currentindex+= ( $bytes_per_pixel - $loopindex );
						fseek($file, $bytes_per_pixel, SEEK_CUR);
					}
					//$im = @ImageCreate ($width, (256*$this->wave_channels)+1) or die ("Cannot Initialize new GD image stream!");
					$im = @ImageCreate ($width, $height) or die ("Cannot Initialize new GD image stream!");
					$background_color = ImageColorAllocate ($im, hexdec(substr($this->visual_background_color,1,2)),hexdec(substr($this->visual_background_color,3,2)),hexdec(substr($this->visual_background_color,5,2)));
					$cBlack = ImageColorAllocate ($im, hexdec(substr($this->visual_background_color,1,2)),hexdec(substr($this->visual_background_color,3,2)),hexdec(substr($this->visual_background_color,5,2)));
					$cGreen = ImageColorAllocate ($im, hexdec(substr($this->visual_graph_color,1,2)),hexdec(substr($this->visual_graph_color,3,2)),hexdec(substr($this->visual_graph_color,5,2)));
					$cRed = ImageColorAllocate ($im, hexdec(substr($this->visual_border_color,1,2)),hexdec(substr($this->visual_border_color,3,2)),hexdec(substr($this->visual_border_color,5,2)));
					$cBlue = ImageColorAllocate ($im, hexdec(substr($this->visual_grid_color,1,2)),hexdec(substr($this->visual_grid_color,3,2)),hexdec(substr($this->visual_grid_color,5,2)));
					if ($this->visual_border)
					{
						ImageRectangle ($im,0,0,($width-1),($height-1),$cRed);
						for ($i=0;$i<=$this->wave_channels;$i++)
						{
							ImageLine ($im,1,($i*($height_channel/2))+($height_channel/2),$width,($i*($height_channel/2))+($height_channel/2),$cRed);
						}
					}
					if ($this->visual_grid)
					{
						for ($i=1;$i<=($width/100*2);$i++)
						{
							ImageLine ($im,$i*50,0,$i*50,(256*$this->wave_channels),$cBlue);
						}
					}

					// this for-loop draws a graph for every channel

					for ($j=0;$j<sizeof($visualData);$j++)
					{
						$last_x = 1;
						$last_y = $height_channel / 2;

						// this for-loop draws the graphs itself

						for ($i=1;$i<sizeof($visualData[$j]);$i++)
						{
							$faktor = 128 / ($height_channel / 2);
							$val = $visualData[$j][$i] / $faktor;
							if ($this->visual_graph_mode == 0)
							{
								ImageLine ($im,$last_x,($last_y+($j*$height_channel)),$i,($val+($j*$height_channel)),$cGreen);
							} else {
								ImageLine ($im,$i,(($height_channel/2)+($j*$height_channel)),$i,($val+($j*$height_channel)),$cGreen);
							}
							$last_x = $i;
							$last_y = $val;
						}
					}

					// change this to generate JPG or direct output to browser
					if (strtolower($this->visual_fileformat) == "jpeg")
					{
						ImageJpeg ($im,$output);
					} else {
						ImagePng ($im,$output);
					}
				}
			}
			fclose ($file);
		} else {
			// AudioSample - AudioFile-Object not initialized!

		}
	}

	// ************************************************************************
	// getSampleInfo extracts the attributes of the AudioFile-Object
	// ************************************************************************

	function getSampleInfo ()
	{
		$valid = true;

		if (strstr(strtoupper($this->wave_filename),"MP3"))
		{
			$this->mp3info ();
		} elseif (substr(strtoupper($this->wave_filename), -3)=="OGG")
		{
			$this->ogginfo ();
		} else {

			$this->wave_size = filesize ($this->wave_filename);
			if ($this->wave_size > 16)
			{
				$file = fopen ($this->wave_filename,"r");
				$chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
				$null = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
				$chunk_id_2 = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
				$this->wave_id = $chunk_id;
				$this->wave_type = $chunk_id_2;
				if (substr($chunk_id,0,2)=="PK")
				{
					// it's a ZIP-file

					$this->wave_id = "ZIP";
					$this->wave_type = "ZIP";
					$this->valid = true;
				} else {
					if ($this->wave_id == "RIFF" && $this->wave_type == "WAVE")
					{
						// it's a Wave-File

						$chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
						$chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
						if ($chunk_id == "fmt ")
						{
							$format_len = $chunk_size;
							$this->wave_compression = $this->shortCalc (fgetc ($file), fgetc ($file),0);
							$this->wave_channels = $this->shortCalc (fgetc ($file), fgetc ($file),0);
							$this->wave_framerate = $this->longCalc (fgetc ($file), fgetc ($file), fgetc ($file), fgetc ($file),0);
							$this->wave_byterate = $this->longCalc (fgetc ($file), fgetc ($file), fgetc ($file), fgetc ($file),0);
							$null = fgetc($file) . fgetc($file);
							$this->wave_bits = $this->shortCalc (fgetc ($file), fgetc ($file),0);
							$read = 16;
							if ($read < $format_len)
							{
								$extra_bytes = $this->shortCalc (fgetc ($file), fgetc ($file),1);
								$j = 0;
								while ($j < $extra_bytes && !feof($file))
								{
									$null = fgetc ($file);
									$j++;
								}
							}
							$chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
							$chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
							if ($chunk_id == "data")
							{
								$this->wave_length = (($chunk_size / $this->wave_channels) / ($this->wave_bits/8)) / $this->wave_framerate;
							} else {
								while ($chunk_id <> "data" && !feof($file))
								{
									$j = 1;
									while ($j <= $chunk_size && !feof($file))
									{
										$null = fgetc ($file);
										$j++;
									}
									$chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
									//print "<br>$chunk_id*";
									$chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
								}
								if ($chunk_id == "data")
								{
									$this->wave_length = (($chunk_size / $this->wave_channels) / ($this->wave_bits/8)) / $this->wave_framerate;
								}

							}
						} else {
							$valid = false;
						}
					} else {
						if ($this->wave_id == "FORM" && $this->wave_type == "AIFF")
						{
							// we have a AIFF file here

							$chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
							$chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
							if ($chunk_id == "COMM")
							{
								$format_len = $chunk_size;
								$this->wave_channels = $this->shortCalc (fgetc ($file), fgetc ($file),1);
								$null = $this->longCalc (fgetc ($file), fgetc ($file), fgetc ($file), fgetc ($file),1);
								$this->wave_bits = $this->shortCalc (fgetc ($file), fgetc ($file),1);
								$null = fgetc ($file) . fgetc ($file);
								$this->wave_framerate = $this->shortCalc (fgetc ($file), fgetc ($file),1);

								$read = 16;
							} else {
								$valid = false;
							}
						} else {
							// probably crap

							$valid = false;
						}
					}
				}
				fclose ($file);
			} else {
				$valid = false;
			}
			return ($valid);
		}
	}

	// ************************************************************************
	// printSampleInfo prints the attributes of the AudioFile-Object
	// ************************************************************************

	function printSampleInfo()
	{
		$g   = "\n\t\t\t";
		$str =  $g."<table border='0'>";
		//print "<tr><td align=right>filename</td>		<td>&nbsp;$this->wave_filename</td></tr>";
		$str .= $g."  <tr><td align=right>".t('id').":</td><td>$this->wave_id</td></tr>";
		$str .= $g."  <tr><td align=right>".t('type').":</td><td>$this->wave_type</td></tr>";
		$str .= $g."  <tr><td align=right>".t('size').":</td><td>$this->wave_size</td></tr>";
		$str .= $g."  <tr><td align=right>".t('compression').":</td><td>".$this->getCompression ($this->wave_compression)."</td></tr>";
		$str .= $g."  <tr><td align=right>".t('channels').":</td><td>$this->wave_channels</td></tr>";
		$str .= $g."  <tr><td align=right>".t('framerate').":</td><td>$this->wave_framerate</td></tr>";
		$str .= $g."  <tr><td align=right>".t('byterate').":</td><td>$this->wave_byterate</td></tr>";
		$str .= $g."  <tr><td align=right>".t('bits').":</td><td>$this->wave_bits</td></tr>";
		$str .= $g."  <tr><td align=right>".t('length').":</td><td>".number_format ($this->wave_length,"2")." sec.<br>&nbsp;".date("i:s", mktime(0,0,round($this->wave_length)))."</td></tr>";

		// ID3V1
		if ($this->id3_tag)
		{
			$id3v1 = $g."  <tr>";
			$id3v1 .= $g."    <td align=right>id3v1-tags</td>";
			$id3v1 .= $g."    <td>";
			$id3v1 .= $g."      <table border='0'>";
			$id3v1 .= $g."        <tr><td align=right>".t('title').":</td><td>" . $this->id3_title.  "</td></tr>";
			$id3v1 .= $g."        <tr><td align=right>".t('artist').":</td><td>". $this->id3_artist. "</td></tr>";
			$id3v1 .= $g."        <tr><td align=right>".t('album').":</td><td>" . $this->id3_album.  "</td></tr>";
			$id3v1 .= $g."        <tr><td align=right>".t('year').":</td><td>"  . $this->id3_year.   "</td></tr>";
			$id3v1 .= $g."        <tr><td align=right>".t('comment').":</td><td>".$this->id3_comment."</td></tr>";
			$id3v1 .= $g."        <tr><td align=right>".t('genre').":</td><td>" . $this->id3_genre.  "</td></tr>";
			$id3v1 .= $g."      </table>";
			$id3v1 .= $g."    </td>";
			$id3v1 .= $g."  </tr>";
		}
		else
		{
			$id3v1 = $g."  <tr><td align=right>id3v1-tags: </td><td>Not found</td></tr>";
		}

		// ID3V2
		if ( isset($this->id3v2) && $this->id3v2)
		{
			$id3v2  = $g."  <tr>";
			$id3v2 .= $g."    <td align=right>".t('id3v2-tags')."</td>";
			$id3v2 .= $g."    <td>";
			$id3v2 .= $g."      <table border='0'>";
			if(isset($this->id3v2->TIT2))$id3v2 .=  $g."      <tr><td align=right>".t('title').":</td><td>"  . __decode($this->id3v2->TIT2)."</td></tr>";
			if(isset($this->id3v2->TPE1))$id3v2 .=  $g."      <tr><td align=right>".t('artist').":</td><td>" . __decode($this->id3v2->TPE1)."</td></tr>";
			if(isset($this->id3v2->TOPE))$id3v2 .=  $g."      <tr><td align=right>".t('original artist').":</td><td>".__decode($this->id3v2->TOPE)."</td></tr>";
			if(isset($this->id3v2->TALB))$id3v2 .=  $g."      <tr><td align=right>".t('album').":</td><td>"  . __decode($this->id3v2->TALB)."</td></tr>";
			if(isset($this->id3v2->TYER))$id3v2 .=  $g."      <tr><td align=right>".t('year').":</td><td>"   . __decode($this->id3v2->TYER)."</td></tr>";
			if(isset($this->id3v2->COMM))$id3v2 .=  $g."      <tr><td align=right>".t('comment').":</td><td>". __decode($this->id3v2->COMM)."</td></tr>";
			if(isset($this->id3v2->TCOM))$id3v2 .=  $g."      <tr><td align=right>".t('composer').":</td><td>".__decode($this->id3v2->TCOM)."</td></tr>";
			if(isset($this->id3v2->TCON))$id3v2 .=  $g."      <tr><td align=right>".t('genre').":</td><td>"  . __decode($this->id3v2->TCON)."</td></tr>";
			if(isset($this->id3v2->TENC))$id3v2 .=  $g."      <tr><td align=right>".t('encoder').":</td><td>". __decode($this->id3v2->TENC)."</td></tr>";
			if(isset($this->id3v2->WXXX))$id3v2 .=  $g."      <tr><td align=right>".t('website').":</td><td>". __decode($this->id3v2->WXXX)."</td></tr>";
			$id3v2 .=  $g."      </table>";
			$id3v2 .=  $g."    </td>";
			$id3v2 .=  $g."  </tr>";
		}
		else
		{
			$id3v2 =  $g."  <tr><td align=right>id3v2 tags: </td><td>Not found</td></tr>";
		}

		// VORBIS
		if ($this->wave_id=="OGG" && isset($this->vorbis_comment))
		{
			$ogg  =  $g."  <tr>";
			$ogg .=  $g."    <td align=right>ogg-tags</td>";
			$ogg .=  $g."    <td>";
			$ogg .=  $g."      <table border='0'>";
			$ogg .=  $g."      <tr><td width=70 align=right>".t('title').":</td><td>".$this->vorbis_comment->TITLE."</td></tr>";
			$ogg .=  $g."      <tr><td align=right>".t('artist').":</td><td>".$this->vorbis_comment->ARTIST."</td></tr>";
			$ogg .=  $g."      <tr><td align=right>".t('album').":</td><td>".$this->vorbis_comment->ALBUM."</td></tr>";
			$ogg .=  $g."      <tr><td align=right>".t('date').":</td><td>".$this->vorbis_comment->DATE."</td></tr>";
			$ogg .=  $g."      <tr><td align=right>".t('genre').":</td><td>".$this->vorbis_comment->GENRE."</td></tr>";
			$ogg .=  $g."      <tr><td align=right>".t('comment').":</td><td>".$this->vorbis_comment->COMMENT."</td></tr>";
			$ogg .=  $g."      </table>";
			$ogg .=  $g."    </td>";
			$ogg .=  $g."  </tr>";
		}
		else
		{
			$ogg =  $g."  <tr><td align=right>ogg vorbis info: </td><td>Not found</td></tr>";
		}

		$str .= $id3v1 . $id3v2 .$ogg;
		$str .=  $g."</table>\n";
		return $str;
	}


	// ************************************************************************
	// loadFile initializes the AudioFile-Object
	// ************************************************************************

	function loadFile ($loadFilename)
	{
		$this->wave_filename = $loadFilename;
		$this->getSampleInfo ();
		$this->visual_graph_color = "#18F3AD";
		$this->visual_background_color = "#000000";
		$this->visual_grid_color = "#002C4A";
		$this->visual_border_color = "#A52421";
		$this->visual_grid = true;
		$this->visual_border = true;
		$this->visual_width = 600;
		$this->visual_height = 512;
		$this->visual_graph_mode = 1;
		$this->visual_fileformat = "png";
	}


	// ************************************************************************
	// getId3v2 loads id3v2 frames into $this->id3v2-><frameid>
	//	- any frame flags are saved in an array called <frameid>_flags
	//	- for instance, song title will be in $this->id3v2->TIT2
	//	   and any flags set in TIT2 would be in array $this->id3v2->TIT2_flags
	//
	// For common frame id codes see http://www.id3.org/id3v2.4.0-frames.txt
	// For more info on format see http://www.id3.org/id3v2.4.0-structure.txt
	// ************************************************************************

	function getId3v2 (&$fp)
	{
		// ID3v2 version 4 support -- see http://www.id3.org/id3v2.4.0-structure.txt
		$footer = 0;

		// id3v2 version
		$tmp = ord(fread($fp,1));
		$tmp2 = ord(fread($fp,1));
		$this->id3v2->version = "ID3v2.".$tmp.".".$tmp2;

		// flags
		$tmp = ord(fread($fp,1));
		if($tmp & 128) $this->id3v2->unsynch = "1";
		if($tmp & 64) $this->id3v2->extended = "1";
		if($tmp & 32) $this->id3v2->experimental = "1";
		if($tmp & 16)
		{
			$this->id3v2->footer = "1";
			$footer = 10;
		}

		// tag size
		$tagsize = $this->get32bitSynchsafe($fp) + $footer;

		// extended header
		if (isset($this->id3v2->extended) && $this->id3v2->extended==1)
		{
			// get extended header size
			$extended_header_size = $this->get32bitSynchsafe($fp) ;

			// load (but ignore) extended header
			$this->id3v2->extended_header= fread($fp, $extended_header_size);
		}

		// get the tag contents
		while ( ftell($fp) < ($tagsize+10) )
		{
			// get next frame header
			$frameid = fread($fp,4);
			if (trim($frameid)=="") break;
			$framesize= $this->get32bitSynchsafe($fp);
			$frameflags0= ord(fread($fp,1));
			$frameflags1= ord(fread($fp,1));

			// frame status flags
			$frameidflags= $frameid."_flags";
			if ($frameflags0 & 128) $this->id3v2->{$frameidflags}['tag_alter_discard'] = 1;
			if ($frameflags0 & 64) $this->id3v2->{$frameidflags}['file_alter_discard'] = 1;
			if ($frameflags0 & 32) $this->id3v2->{$frameidflags}['readonly'] = 1;

			// frame format flags
			if ($frameflags1 & 128) $this->id3v2->{$frameidflags}['group'] = 1;
			if ($frameflags1 & 16) $this->id3v2->{$frameidflags}['compressed'] = 1;
			if ($frameflags1 & 8) $this->id3v2->{$frameidflags}['encrypted'] = 1;
			if ($frameflags1 & 4) $this->id3v2->{$frameidflags}['unsyrchronised'] = 1;
			if ($frameflags1 & 2) $this->id3v2->{$frameidflags}['data_length_indicator'] = 1;

			// get frame contents
			$this->id3v2->{$frameid} = trim(fread($fp, $framesize));
		}

		// position $fp at end of id3v2header
		fseek($fp, ($tagsize + 10), SEEK_SET);
		return 1;
	}


	// ************************************************************************
	// get32bitSynchsafe returns a converted integer from an ID3v2 tag
	// ************************************************************************

	function get32bitSynchsafe(&$fp)
	{
		/* Synchsafe integers are
		 integers that keep its highest bit (bit 7) zeroed, making seven bits
		out of eight available. Thus a 32 bit synchsafe integer can store 28
		bits of information.
		*/
		$tmp = ord(fread($fp,1)) & 127;
		$tmp2 = ord(fread($fp,1)) & 127;
		$tmp3 = ord(fread($fp,1)) & 127;
		$tmp4 = ord(fread($fp,1)) & 127;
		$converted = ($tmp * 2097152) + ($tmp2 * 16384) + ($tmp3 * 128) + $tmp4;
		return $converted;
	}


	// ************************************************************************
	// ogginfo gets format, duration, and metadata from Ogg Vorbis files
	// 	- metadata (comment header) information is saved in
	//	   $this->vorbis_comment-><fieldname>
	//	- for instance, the song title will be in $this->vorbis_comment->title
	//	- WARNING: values may be arrays because the Vorbis spec allows multiple fields
	//	  with the same name (eg, $this->vorbis_comment->artist[0] and
	//	  $this->vorbis_comment->artist[1] for a duet)
	//
	// For more info on Ogg bitstream containers, see http://www.xiph.org/ogg/vorbis/doc/framing.html
	// For more info on Vorbis, see http://www.xiph.org/ogg/vorbis/doc/Vorbis_I_spec.html
	// ************************************************************************

	function ogginfo ()
	{
		$fp = fopen($this->wave_filename,"r");

		// Ogg stream?
		$capture_pattern= fread($fp,4);
		if ($capture_pattern!="OggS")
		{
			// not an Ogg stream
			fclose($fp);
			return 0;
		}
		rewind($fp);

		// find the next page, then
		$this->findVorbis($fp);
		$packet_type= ord(fread($fp,1));
		$preamble= fread($fp,6);

		if ($packet_type==1)
		{
			/* IDENTIFICATION HEADER
			 1) [vorbis_version] = read 32 bits as unsigned integer
			2) [audio_channels] = read 8 bit integer as unsigned
			3) [audio_sample_rate] = read 32 bits as unsigned integer
			4) [bitrate_maximum] = read 32 bits as signed integer
			5) [bitrate_nominal] = read 32 bits as signed integer
			6) [bitrate_minimum] = read 32 bits as signed integer
			7) [blocksize_0] = 2 exponent (read 4 bits as unsigned integer) -- IGNORING
			8) [blocksize_1] = 2 exponent (read 4 bits as unsigned integer) -- IGNORING
			9) [framing_flag] = read one bit -- IGNORING
			*/
			$identification= unpack('L1vorbis_version/C1audio_channels/L1audio_sample_rate/L1bitrate_maximum/L1bitrate_nominal/L1bitrate_minimum', fread($fp,21));
			//print "<pre>".print_r($identification,1)."</pre>";
		}

		// find the next header, then
		$this->findVorbis($fp);
		$packet_type= ord(fread($fp,1));
		$preamble= fread($fp,6);

		if ($packet_type==3)
		{
			/* COMMENT HEADER
				1) [vendor_length] = read an unsigned integer of 32 bits
			2) [vendor_string] = read a UTF-8 vector as [vendor_length] octets
			3) [user_comment_list_length] = read an unsigned integer of 32 bits
			4) iterate [user_comment_list_length] times {
			5) [length] = read an unsigned integer of 32 bits
			6) this iteration's user comment = read a UTF-8 vector as [length] octets
			}
			7) [framing_bit] = read a single bit as boolean

			Note that there may be more than one instance of any field
			*/
			$vendor= unpack('L1vendor_length', fread($fp,4));
			$vendor['vendor_string']= fread($fp, $vendor['vendor_length']);
			$list= unpack('L1user_comment_list_length', fread($fp,4));
			for ($i=0; $i<$list['user_comment_list_length']; $i++)
			{
				$length= unpack('L1length', fread($fp,4));
				$temp= fread($fp, $length['length']);
				$array= explode("=",$temp,2);

				// field names are case-insensitive
				$array[0]= strtoupper( $array[0] );

				/*
				 EXPLANATION OF THE FOLLOWING LOGIC
				If there is only one artist field, it will be at $this->vorbis_comment->ARTIST, handled by the final else below
				If a second one is found, $this->vorbis_comment->ARTIST will be converted to an array with two artist values.
				This is done by the if statement.
				Any additional artist fields will be pushed onto the end of the $this->vorbis_comment->ARTIST array by the elseif
				*/

				if ($this->vorbis_comment->{$array[0]}!="" && !is_array( $this->vorbis_comment->{$array[0]}) )
				{
					// second instance, convert to array
					$temp= $this->vorbis_comment->{$array[0]};
					$this->vorbis_comment->{$array[0]}= array( $temp, $array[1] );
				}
				elseif ( is_array( $this->vorbis_comment->{$array[0]}) )
				{
					// third through nth instances, add to array
					array_push( $this->vorbis_comment->{$array[0]}, $array[1] );
				}
				else
				{
					// first instance
					$this->vorbis_comment->{$array[0]}= $array[1];
				}
			}
			//print "<pre>".print_r($this->vorbis_comment,1)."</pre>";
		}

		// find length (number of samples, ay?) -- last page will have total samples info, see below
		$filesize= filesize($this->wave_filename);
		if ($filesize > 12288) $nearend= -12288;
		else $nearend= (0 - $filesize);
		fseek($fp, $nearend, SEEK_END);

		// look for page of type 4 or higher (0x04 == end-of-stream)
		while($type < 4 && !feof($fp)) {
			$type= $this->findOggPage($fp);
		}

		// found the end of stream page...
		// the next 8 bytes are the absolute granule position:
		/*
			"The position specified is the total samples encoded after
		including all packets finished on this page (packets begun
				on this page but continuing on to the next page do not count).
		The rationale here is that the position specified in the frame
		header of the last page tells how long the data coded by the
		bitstream is. "
		*/
		$bytes[0]= ord(fread($fp,1));
		$bytes[1]= ord(fread($fp,1));
		$bytes[2]= ord(fread($fp,1));
		$bytes[3]= ord(fread($fp,1));
		$bytes[4]= ord(fread($fp,1));
		$bytes[5]= ord(fread($fp,1));
		$bytes[6]= ord(fread($fp,1));
		$bytes[7]= ord(fread($fp,1));
		foreach ($bytes AS $exp=>$value)
		{
			$samples+= ($value * pow(256, $exp));
		}
		$seconds= round(($samples / $identification['audio_sample_rate']), 2);
		$min= floor($seconds/60);
		$sec= $seconds - ($min * 60);
		$duration= "$min:$sec";
		//print "$samples samples / $seconds seconds ($duration)";

		fclose($fp);

		// transfer the extracted data into classAudioFile-structure
		$this->wave_id = "OGG";
		$this->wave_type = "Ogg Bitstream";
		$this->wave_compression = "Vorbis version 1.".$identification['vorbis_version'];
		$this->wave_channels = $identification['audio_channels'];
		$this->wave_framerate = $identification['audio_sample_rate'];
		$this->wave_byterate = ($identification['bitrate_nominal']/1000)." Kbits/sec.";
		$this->wave_bits = "n/a";
		$this->wave_size = $filesize;
		$this->wave_length = $seconds;
		return 1;
	}

	// ************************************************************************
	// findVorbis finds the start of the next Vorbis header in an Ogg bitstream
	// ************************************************************************

	function findVorbis(&$fp)
	{
		// find the next header, then
		$capture_pattern= fread($fp, 6);
		while ($capture_pattern!="vorbis" && !feof($fp))
		{
			// yes, character by character, fun!
			$capture_pattern= substr($capture_pattern, 1).fread($fp, 1);
			//print ". ";
		}
		//print "Found header ".(ftell($fp)-7)."<br>";

		// back up the pointer by 7 to start of header
		fseek($fp, -7, SEEK_CUR);
	}

	// ************************************************************************
	// findOggPage finds the next logical page in an Ogg bitstream, and returns the page type flag
	// ************************************************************************

	function findOggPage(&$fp)
	{
		// find the next header, then
		$capture_pattern= fread($fp, 4);
		while ($capture_pattern!="OggS" && !feof($fp))
		{
			// yes, character by character, fun!
			$capture_pattern= substr($capture_pattern, 1).fread($fp, 1);
			//print ". ";
		}
		$version= fread($fp,1);
		$type= ord(fread($fp,1));
		//print "Found page ".sprintf('%08b',$type)." ".(ftell($fp)-6)."<br>";
		return $type;
	}

}


/*
 php5 class (will not work in php4)
for detecting bitrate and duration of regular mp3 files (not VBR files)
*/

//-----------------------------------------------------------------------------
class mp3file
{
	protected $block;
	protected $blockpos;
	protected $blockmax;
	protected $blocksize;
	protected $fd;
	protected $bitpos;
	protected $mp3data;
	public function __construct($filename)
	{
		$this->powarr  = array(0=>1,1=>2,2=>4,3=>8,4=>16,5=>32,6=>64,7=>128);
		$this->blockmax= 1024;

		$this->mp3data = array();
		$this->mp3data['Filesize'] = filesize($filename);

		$this->fd = fopen($filename,'rb');
		$this->prefetchblock();
		$this->readmp3frame();
	}
	public function __destruct()
	{
		fclose($this->fd);
	}
	//-------------------
	public function get_metadata()
	{
		return $this->mp3data;
	}
	protected function readmp3frame()
	{
		$iscbrmp3=true;
		if ($this->startswithid3())
			$this->skipid3tag();
		else if ($this->containsvbrxing())
		{
			$this->mp3data['Encoding'] = 'VBR';
			$iscbrmp3=false;
		}
		else if ($this->startswithpk())
		{
			$this->mp3data['Encoding'] = 'Unknown';
			$iscbrmp3=false;
		}

		if ($iscbrmp3)
		{
			$i = 0;
			$max=5000;
			//look in 5000 bytes...
			//the largest framesize is 4609bytes(256kbps@8000Hz  mp3)
			for($i=0; $i<$max; $i++)
			{
			//looking for 1111 1111 111 (frame synchronization bits)
				if ($this->getnextbyte()==0xFF)
				if ($this->getnextbit() && $this->getnextbit() && $this->getnextbit())
					break;
			}
			if ($i==$max)
					$iscbrmp3=false;
			}

			if ($iscbrmp3)
			{
				$this->mp3data['Encoding'         ] = 'CBR';
				$this->mp3data['MPEG version'     ] = $this->getnextbits(2);
				$this->mp3data['Layer Description'] = $this->getnextbits(2);
				$this->mp3data['Protection Bit'   ] = $this->getnextbits(1);
				$this->mp3data['Bitrate Index'    ] = $this->getnextbits(4);
				$this->mp3data['Sampling Freq Idx'] = $this->getnextbits(2);
				$this->mp3data['Padding Bit'      ] = $this->getnextbits(1);
				$this->mp3data['Private Bit'      ] = $this->getnextbits(1);
						$this->mp3data['Channel Mode'     ] = $this->getnextbits(2);
								$this->mp3data['Mode Extension'   ] = $this->getnextbits(2);
										$this->mp3data['Copyright'        ] = $this->getnextbits(1);
												$this->mp3data['Original Media'   ] = $this->getnextbits(1);
														$this->mp3data['Emphasis'         ] = $this->getnextbits(1);
																$this->mp3data['Bitrate'          ] = mp3file::bitratelookup($this->mp3data);
																$this->mp3data['Sampling Rate'    ] = mp3file::samplelookup($this->mp3data);
																$this->mp3data['Frame Size'       ] = mp3file::getframesize($this->mp3data);
																$this->mp3data['Length'           ] = mp3file::getduration($this->mp3data,$this->tell2());
																$this->mp3data['Length mm:ss'     ] = mp3file::seconds_to_mmss($this->mp3data['Length']);

																		if ($this->mp3data['Bitrate'      ]=='bad'     ||
																$this->mp3data['Bitrate'      ]=='free'    ||
																$this->mp3data['Sampling Rate']=='unknown' ||
																$this->mp3data['Frame Size'   ]=='unknown' ||
																$this->mp3data['Length'     ]=='unknown')
																	$this->mp3data = array('Filesize'=>$this->mp3data['Filesize'], 'Encoding'=>'Unknown');
				}
				else
				{
				if(!isset($this->mp3data['Encoding']))
						$this->mp3data['Encoding'] = 'Unknown';
				}
	}
	protected function tell()
    {
    return ftell($this->fd);
}
protected function tell2()
{
    return ftell($this->fd)-$this->blockmax +$this->blockpos-1;
    }
    protected function startswithid3()
    {
    return ($this->block[1]==73 && //I
    $this->block[2]==68 && //D
    $this->block[3]==51);  //3
    }
    	protected function startswithpk()
    	{
    	return ($this->block[1]==80 && //P
    	$this->block[2]==75);  //K
    	}
    	protected function containsvbrxing()
    	{
    	//echo "<!--".$this->block[37]." ".$this->block[38]."-->";
    	//echo "<!--".$this->block[39]." ".$this->block[40]."-->";
    	return(
    			($this->block[37]==88  && //X 0x58
    			$this->block[38]==105 && //i 0x69
    			$this->block[39]==110 && //n 0x6E
    		$this->block[40]==103)   //g 0x67
    		/*               ||
    		($this->block[21]==88  && //X 0x58
    		$this->block[22]==105 && //i 0x69
    		$this->block[23]==110 && //n 0x6E
    		$this->block[24]==103)   //g 0x67*/
    	);

    	}
    	protected function debugbytes()
    	{
    	for($j=0; $j<10; $j++)
    	{
    	for($i=0; $i<8; $i++)
    	{
    	if ($i==4) echo " ";
    	echo $this->getnextbit();
    	}
    	echo "<BR>";
    	}
    	}
    	protected function prefetchblock()
    	{
    	$block = fread($this->fd, $this->blockmax);
    	$this->blocksize = strlen($block);
    	$this->block = unpack("C*", $block);
    	$this->blockpos=0;
    	}
    	protected function skipid3tag()
    		{
    			$bits=$this->getnextbits(24);//ID3
    			$bits.=$this->getnextbits(24);//v.v flags

    			//3 bytes 1 version byte 2 byte flags
    			$arr = array();
    			$arr['ID3v2 Major version'] = bindec(substr($bits,24,8));
    			$arr['ID3v2 Minor version'] = bindec(substr($bits,32,8));
    			$arr['ID3v2 flags'        ] = bindec(substr($bits,40,8));
    			if (substr($bits,40,1)) $arr['Unsynchronisation']=true;
    			if (substr($bits,41,1)) $arr['Extended header']=true;
    			if (substr($bits,42,1)) $arr['Experimental indicator']=true;
    			if (substr($bits,43,1)) $arr['Footer present']=true;

    			$size = "";
        for($i=0; $i<4; $i++)
    			{
    			$this->getnextbit();//skip this bit, should be 0
    			$size.= $this->getnextbits(7);
    			}

    			$arr['ID3v2 Tags Size']=bindec($size);//now the size is in bytes;
        if ($arr['ID3v2 Tags Size'] - $this->blockmax>0)
    	{
            fseek($this->fd, $arr['ID3v2 Tags Size']+10 );
            $this->prefetchblock();
																if (isset($arr['Footer present']) && $arr['Footer present'])
																{
																for($i=0; $i<10; $i++)
																$this->getnextbyte();//10 footer bytes
            }
    			}
    			else
    			{
    			for($i=0; $i<$arr['ID3v2 Tags Size']; $i++)
    			$this->getnextbyte();
    			}
    			}

    			protected function getnextbit()
    			{
    			if ($this->bitpos==8)
    				return false;

        $b=0;
        $whichbit = 7-$this->bitpos;
        $mult = $this->powarr[$whichbit]; //$mult = pow(2,7-$this->pos);
        $b = $this->block[$this->blockpos+1] & $mult;
        $b = $b >> $whichbit;
        	$this->bitpos++;

        if ($this->bitpos==8)
        {
        $this->blockpos++;

        	if ($this->blockpos==$this->blockmax) //end of block reached
        	{
        	$this->prefetchblock();
        	}
        	else if ($this->blockpos==$this->blocksize)
        	{//end of short block reached (shorter than blockmax)
        	return;//eof
        	}

        	$this->bitpos=0;
        	}
        	return $b;
        	}
        	protected function getnextbits($n=1)
        		{
        		$b="";
        		for($i=0; $i<$n; $i++)
        			$b.=$this->getnextbit();
        			return $b;
        	}
        		protected function getnextbyte()
        		{
        		if ($this->blockpos>=$this->blocksize)
        			return;

        			$this->bitpos=0;
        			$b=$this->block[$this->blockpos+1];
        		$this->blockpos++;
        		return $b;
        		}
        		//-----------------------------------------------------------------------------
        			public static function is_layer1(&$mp3) { return ($mp3['Layer Description']=='11'); }
        			public static function is_layer2(&$mp3) { return ($mp3['Layer Description']=='10'); }
        			public static function is_layer3(&$mp3) { return ($mp3['Layer Description']=='01'); }
        			public static function is_mpeg10(&$mp3)  { return ($mp3['MPEG version']=='11'); }
        			public static function is_mpeg20(&$mp3)  { return ($mp3['MPEG version']=='10'); }
        			public static function is_mpeg25(&$mp3)  { return ($mp3['MPEG version']=='00'); }
        			public static function is_mpeg20or25(&$mp3)  { return ($mp3['MPEG version']{1}=='0'); }
    //-----------------------------------------------------------------------------
    public static function bitratelookup(&$mp3)
    {
    //bits               V1,L1  V1,L2  V1,L3  V2,L1  V2,L2&L3
        			$array = array();
        			$array['0000']=array('free','free','free','free','free');
        			$array['0001']=array(  '32',  '32',  '32',  '32',   '8');
        			$array['0010']=array(  '64',  '48',  '40',  '48',  '16');
        			$array['0011']=array(  '96',  '56',  '48',  '56',  '24');
        			$array['0100']=array( '128',  '64',  '56',  '64',  '32');
        				$array['0101']=array( '160',  '80',  '64',  '80',  '40');
        						$array['0110']=array( '192',  '96',  '80',  '96',  '48');
        								$array['0111']=array( '224', '112',  '96', '112',  '56');
        										$array['1000']=array( '256', '128', '112', '128',  '64');
        										$array['1001']=array( '288', '160', '128', '144',  '80');
        										$array['1010']=array( '320', '192', '160', '160',  '96');
        										$array['1011']=array( '352', '224', '192', '176', '112');
        										$array['1100']=array( '384', '256', '224', '192', '128');
        										$array['1101']=array( '416', '320', '256', '224', '144');
        										$array['1110']=array( '448', '384', '320', '256', '160');
        										$array['1111']=array( 'bad', 'bad', 'bad', 'bad', 'bad');

        										$whichcolumn=-1;
        												if      (mp3file::is_mpeg10($mp3) && mp3file::is_layer1($mp3) )//V1,L1
        												$whichcolumn=0;
        														else if (mp3file::is_mpeg10($mp3) && mp3file::is_layer2($mp3) )//V1,L2
        																$whichcolumn=1;
        																		else if (mp3file::is_mpeg10($mp3) && mp3file::is_layer3($mp3) )//V1,L3
        																		$whichcolumn=2;
        																		else if (mp3file::is_mpeg20or25($mp3) && mp3file::is_layer1($mp3) )//V2,L1
        																		$whichcolumn=3;
        																		else if (mp3file::is_mpeg20or25($mp3) && (mp3file::is_layer2($mp3) || mp3file::is_layer3($mp3)) )
        																			$whichcolumn=4;//V2,   L2||L3

        																			if (isset($array[$mp3['Bitrate Index']][$whichcolumn]))
        																			return $array[$mp3['Bitrate Index']][$whichcolumn];
        																			else
        																				return "bad";
        			}
    //-----------------------------------------------------------------------------
    public static function samplelookup(&$mp3)
        														{
        														//bits               MPEG1   MPEG2   MPEG2.5
        														$array = array();
        														$array['00'] =array('44100','22050','11025');
        															$array['01'] =array('48000','24000','12000');
        																	$array['10'] =array('32000','16000','8000');
        															$array['11'] =array('res','res','res');

        															$whichcolumn=-1;
        			if      (mp3file::is_mpeg10($mp3))
            $whichcolumn=0;
            else if (mp3file::is_mpeg20($mp3))
            $whichcolumn=1;
            else if (mp3file::is_mpeg25($mp3))
            	$whichcolumn=2;

            	if (isset($array[$mp3['Sampling Freq Idx']][$whichcolumn]))
            	return $array[$mp3['Sampling Freq Idx']][$whichcolumn];
            	else
            		return 'unknown';
        				}
        				//-----------------------------------------------------------------------------
        				public static function getframesize(&$mp3)
        					{
        if ($mp3['Sampling Rate']>0)
        					{
        					return  ceil((144 * $mp3['Bitrate']*1000)/$mp3['Sampling Rate']) + $mp3['Padding Bit'];
        				}
        				return 'unknown';
        			}
        			//-----------------------------------------------------------------------------
        			public static function getduration(&$mp3,$startat)
        				{
        				if ($mp3['Bitrate']>0)
        				{
            $KBps = ($mp3['Bitrate']*1000)/8;
            $datasize = ($mp3['Filesize'] - ($startat/8));
            		$length = $datasize / $KBps;
            				return sprintf("%d", $length);
        			}
        			return "unknown";
        			}
        			//-----------------------------------------------------------------------------
    public static function seconds_to_mmss($duration)
    {
        return sprintf("%d:%02d", ($duration /60), $duration %60 );
    }
}


