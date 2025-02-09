# Leto APIs
This repo holds the APIs that powers the front end of Leto Car pooling E-hailing application

# Setting up for development
- Install `XAMPP` or its equivalent
- clone this repo in the `htdocs` folder or its equivalent
- Install php composer for dependency management
- run `composer install` in the root folder to download all dependencies
- Set up your `.env` file following the `.env-example` provided. Only change the variable values. This is because the `Utility::getEnv()` function will return an Object with properties, for example, `dbName` for `DB_NAME`, in the `.env` file; and these are already used somewhere. I am sure you don't want things to crash.
- Set up your `/api/includes/passwords.inc.php` following the `/api/includes/example-passwords.inc.php`. You will notice that you need the password for Leto's email and your own twilio account SID and auth token. Also, the firebase and google maps credentials too.
- create a MySQL database with the name in your `.env` file to hold the information from the API. Put the database credentials in the `.env` file.
- Turn on your servers and from the root directory, run `php ./api/database/migration.php` to create the database. Each time you run this command, the database is dropped and recreated.
- You can install the VS Code extension called `Thunder Client` for making API requests and testing. Also, you can use `Postman`. `Postman` will be used for general testing of the API.

# Dependencies
1. **phpmailer/phpmailer**: For handling Emails
2. **yidas/google-maps-services**: For google maps api services from PHP.
3. **kreait/firebase-php**: For firebase interaction from the server
4. **rmccue/requests**: For wrapping http requests. Staying away from raw cUrl
