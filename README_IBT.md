# I, Librarian Installation at IBT

### Set up Apache + PHP stack

* Apache shipped with Mac OS can be used
* PHP shipped with Mac OS >= 10.10 does not include GD and thus cannot be used
* PHP built with GD can be installed from: http://php-osx.liip.ch/

Make the following changes in /etc/apache2/httpd.conf:

* Enable php by adding the line (or similar):

`LoadModule php7_module /usr/local/php5/libphp7.so`

* Add a new Directory directive by inserting:

```
apache_conf
<Directory /Volumes/Daten/ilibrarian>
    <FilesMatch "\.(ini|conf)$">
        Require all denied
    </FilesMatch>
</Directory>
<Directory /Volumes/Daten/ilibrarian/library>
    Require all denied
</Directory>
```

Make the following changes in /usr/local/php5/php.d/99-liip-developer.ini:

* Add the lines:

```
upload_max_filesize = 200M
post_max_size = 800M
max_input_vars = 10000
```

* Change the date settings to:

```
date.timezone = "Europe/Berlin"
date.default_latitude  = 49.0047 ; Karlsruhe
date.default_longitude = 8.3858  ; Karlsruhe
```

### Install additional dependencies:

* Popper, Ghostscript and Tesseract OCR using Homebrew:
  `brew install poppler ghostscript tesseract`
  
  * Download additional Tesseract language files from https://github.com/tesseract-ocr/tessdata/tree/3.04.00:
    `curl https://raw.githubusercontent.com/tesseract-ocr/tessdata/3.04.00/deu.traineddata --output /usr/local/share/tessdata/deu.traineddata`
    `curl https://raw.githubusercontent.com/tesseract-ocr/tessdata/3.04.00/fra.traineddata --output /usr/local/share/tessdata/fra.traineddata`
    `curl https://raw.githubusercontent.com/tesseract-ocr/tessdata/3.04.00/spa.traineddata --output /usr/local/share/tessdata/spa.traineddata`

* (LibreOffice from DMG image: https://www.libreoffice.org/download)

### Set up I, Librarian

Clone GitHub repository:

* `cd ~/Sites`
* `git clone git@github.com:steffenschuler/i-librarian-ibt.git`

Set the correct privileges:

* Change the owner of the *library* sub-folder to the Apache user *_www*:
  `chown -R _www:_www /Volumes/Daten/librarian/library`

Setup admin user and remote sign-in:

* Create a first user "admin" (required, as public notes of references are stored as the first users privat notes)
* Enable remote sign-in in ilibrarian.ini
* Remotely sign in with an IBT user that shall become the future admin
* Disable remote sign-in again
* Sign in as "admin" and grant the IBT user "Super User" permissions
* Enable remote sign-in again
