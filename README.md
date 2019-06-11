# CarTalk

[CarTalk](https://en.wikipedia.org/wiki/Car_Talk) was a weekly radio show on NPR that my Dad used to listen to when I was a kid.  I have memories of him in the garage working on stuff and he would always have it on.  The show ran from 1977-2012.

While the entire archive is available for purchase, the podcast files going back to approx 2007 are available for free.  This script scans the podcast listings for availability and downloads the files for archive storage.

## How To Run

This script will download the mp3 files to the ``storage`` directory.

```
$ git clone git@github.com:swt83/php-cartalk-downloader.git cartalk
$ cd cartalk
$ composer update
$ php run
```

Rerunning the app will auto-skip the files that already exist on your hard drive.  It took me about 24 hours to download the entire archive.  The total filesize was 130 GB.