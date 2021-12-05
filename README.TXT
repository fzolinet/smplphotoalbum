Simple Photoalbum 7.x-2.4
~~~~~~~~~~~~~~~~~~~~~~~~~
Features
~~~~~~~~
- The modul works as a Drupal filter of any nodes on it.
- It makes a table or list of images, videos, documents compressed or executable files 
  from a directory on the server.
- When the node is viewed first time the module makes 
- thumbnails from the images,
- the thumbnails saves into the _tn_ subdirectory,
- save the datas of files into the database table 
- makes automatically thumbnails and insert into a table of database.

Installation:
~~~~~~~~~~~~
- Install the Lightbox2 or Colorbox module as usual
- Copy the files of module into the modules directory and enable.
- Set the appropriate properties in the admin/config/fz/smplphotoalbum   
- In the admin/config/content/formats
  Enable the Simpla Photoalbum and Lightbox or Colorbox filteres in the Full HTML type.

Defaults
~~~~~~~~
- The thumbnails showed in table format
- The table has pager
- The admin sets what kind of files enables / disables. It can 
  - image (default), 
  - videos (default) normal or HTML5 Video format
  - audio (default) HTML5 Audio format
  - document, 
  - compressed,
  - executable,
  - other types, 
  - prohibited 
  files.
- The admin can change the extension of files
- You can not acces the files direct!

Events
~~~~~~
If you click on a thumbnail:
- image: showing in original size (in colorbox)
- Video: downloads or plays
- audio: downloads or plays
- documents, compressed, executable downloads
- HTML5 Audio and Video: plays the media,

Administration settings
~~~~~~~~~~~~~~~~~~~~~~~
- Maximum number columns on a page (default 4)
- Mobile version of maximum numbers (default: 5)
- Maximum number of table rows on a page
- Maximum width of thumbnail: (default 150px)
- CSS class of thumbnails (default: smpl_image)

- Root folder of galleries:
  This is absolute place in the filesystem. If you use in the path the 
  __DRUPALROOT__ then it substitutes the Drupal path with it.
- Rebuild of pathes of menus (for developers)
- direct access of files (enable/disable)
- Shows the URL of associated with image (enable/disable)
- Default target of link of image
- Usin taxonomy Photoalbum (enable/disable) 

- Social Network Sharing (OpenGraph protocol for Facebook, Metatag for Twitter)

The admin can enable / disable the  
- Shows the number of views (downloads) of images (files)
- Voting for the image (file) and shows the 
- Average votes anf number of votes (Shows the rank of files)
- Link for the other properties of images 
    (exif info, bitrate, etc.)
- Caption of files
- Substitute of files
- Link of files (URLof original website etc...)    

- There are buttons below the files and you can
  - Edit images online (if installed GD or ImageMagic module on PHP)
  - Edit the subtitles (caption, link) of images   
  - Hide from the website
  - Delete from database and directory  

- Simple Photoalbum temporary folder for Editing the images
- Change the order of files / images
- Using filter in lists (enable / disable)   
- Check and delete the wrong links from database
- Change or delete the subtitles automatically (string substitues with a space)
- File types (enable / disable). Let's see on top of this document
- Set the lists of extensions of any types
- Make preview of mpeg files if the php_ffmpeg module is installed

Using:
~~~~~
You have to use on the node the syntax

You want to make a simple photoalbum:

  [smpl|relative path from photoalbum root]
  
  or
  if you want to change parameters of this photoalbum
  [smpl|relative path from photoalbum root| par01 => value | par02 => value |....]
  
  Parameters:
  cols : number of columns 
  rows : Number of rows 
  width: width of thumbnails in pixel
  order: asc /desc / rand, default asc
  sort: sortable of this album - (True/False) 
  sub: is there subtitle (true / false)
  capt: is there caption (true / false)
  viewed: shows the number of views
  voted: shows the number of votes and rank
  edit: Editable images (true / false)
  exif: shows the exif onformation of images
  audio: shows this type of files (true / false)
  video:shows this type of files (true / false)
  doc:shows this type of files (true / false)
  cmp:shows this type of files (true / false)
  app:shows this type of files (true / false)
  issocial: Facebook like button
  url: shows the URL of image website
  target: what is the target (default: _blank)
  taxes: shows the taxonomies of file
  html5: the video and autio file shows with HTML5 audio and video tags
  mobile: when the user view from mobile the album shows in mobile CSS 
  
  You can use this with taxonomies.
    
  [smplt|taxonomy => taxonomy name] - images from a taxonomy 
  
  You canb shows random photos from the path:
  [smplr|relative path from photoalbum root] - random image from that folder and subfolders

  if the path begins with '/' sign or on Windows 'C:' or something like that, the path is
  absolute from beginning of the filesystem.

Default example:
[smpl|myalbum/foo/]

More example
[smpl|myalbum/foo/|rows=>6|cols=>5|width=>100|sort=>asc]
[smplr|myalbum/foo/]

2018.06
Zoltan Fabian

fz@fzolee.hu
http://www.fzolee.hu
