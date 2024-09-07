All scripts made by me through ChatGPT are MIT. THe mediawiki ones are GPL and their license is included in the code. None of them were actually really made by me, I just got ChatGPT to make them and edited them a bit for them to work.

The best way to run this script is to run Script.py, then gettitles.py. You can put all the titles in a wikimedia's https://tgstation13.org/wiki/Special:Export to make it easier, but to import images you'll need to run script.py.

Run the maintenace scripts to import all images and the .xml files

Add $wgHashedUploadDirectory = true; to make the images work in Localsettings.php of the mediawiki directory

After that, put the two php deleterevision scripts in the maintenance folder, run them with php run.php (scripthere).php --delete and they will revert all changes to that date. Run the deleterevisions one first and then the other one.
