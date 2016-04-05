This module makes gallerie from directories on the server
- makes automatically thumbnails and insert into a table of database
- If you are admin you can change the subtitles of pictures
- You can show random picture from database

Installation:
~~~~~~~~~~~~
- Install the Lightbox2 module as usual
- Copy the files of module into the modules directory and enable.

- Set the appropriate properties in the Administration / website settings / smplphotoalbum
    Default settings:
    Maximum number of table columns: 4
    Maximum number of table rows: 5
    Class of thumbnails of simple photoalbum:
    Root folder of galleries: This is the absolute path from the server root
- In the admin/config/content/formats
  Make a special gallery input filter and enable the Lightbox filter and the SmplPhotoalbum tag

using:
~~~~~
  If you have pictures in a folder you make a node and setting the input filter.
  You can set the input sg. like that:
  [smpl|relative path from photoalbum root| columns number | rows number |width of thumbnails | sort or random]
  [smplt|taxonomy => taxonomy name] - images from a taxonomy 
  [smplr|relative path from photoalbum root] - random image from that folder and subfolders

default example:
[smpl|myalbum/foo/]

More example
[smpl|myalbum/foo/|6|5|100|asc]
[smplr|myalbum/foo/]

cols: number
rows: number
width: only number in pixel
The order can 'asc', 'desc', 'rand'

There will be a simple pager when the pictures are more than one page.
Every image has
- Edit button when you enable on the admin settings
- Delete button, but the module does not erase the image only set the deleted bit in the table
- Kill button and you erase the file from filesystem (if the php has enough permission ) and from database.

- Facebook button, with it a picture will be the OpenGraph meta tag when you share this page on the Facebook.

- links to show the exif informations of images
- view number - from the database
- vote number and average vote rate


2014.07
Zoltan Fabian

fz@fzolee.hu
http://www.fzolee.hu
