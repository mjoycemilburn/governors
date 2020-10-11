## Step 1

This document expects that you are familiar with the procedure for procedure for obtaining an ISP account, registering a UR and uploading files onto public_html, so it is assumed that your starting point is the download of the zip file for the latest release of "governors".

Once you've downloaded this, unzip the files and then upload them into an appropriate directory on the server. It is suggested that if you have more than one system on your URL that you give each system its own directory. In this case the entire unzipped file hierarchy might be uploaded into a /governors directory, for example. Alternatively, you might as well just upload into public_html - the software uses relative addressing so won't mind.

## Step 2

Your next task is to create a database for the system. The name of this can be whatever you choose but you need to use the sql script in mysql_tables to initialise it. To get things going off to a flying start, the database script uploads governor data for the dummy "Newbiggin" school as well as creating database structure and creates access credentials test/tst$ for its dummy clerk. Note, however, that source and pdf files for the documents described in the documents table are not included in the package and so the "get document link" buttons won't work properly.

You now need to create connect and disconnect php scripts for the database and place them in your server's root directory. The system expects these to be called connect_governors_db.php and disconnect_governors_db.php. Templates for these files can be found in the connect_governors_db_prototype.php and disconnect_governors_db_prototype.php files included in the package. Obviously you need to set the 'hostname','root','password' and 'database_name' fields in the templates with the corresponding settings for the database you've just created.

## Step 3

The system provides a mechanism for the LEA administrator to register new schools in the system and issue access credentials for their clerks. This creates an issue over the security of this procedure.  The lea.html page, whose primary responsibility is to provide the LEA administrator with the ability to view the governor records for a school also incudes, at its foot, a special login to provide access to the credentials administration screens. The password for this login is delivered by placing a hidden file, lea_credentials.php, in the server root. First edit this to give the lea administrator an appropriate password (it is currently set to 'cumbria%') and then move the file into the root directory.

Alternatively it would be possible to create an automated arrangement whereby schools registered themselves but, for the present, development of such an arrangement is left simply as a future possibility.

MartinJ : Oct 11 2020
