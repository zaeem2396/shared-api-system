<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Setup

- Clone this repository.
- Create a .env file, copy the content present in .env.example and run ```php artisan key:generate``` command.
- Add this line in host entry ```127.0.0.1 app.dev.sas.com```
- Configure port number in docker-compose.yml file.
- Run this command to create containers ```docker compose up --build``` (For windows start docker desktop before running this command).
- After the containers are successfully created hit this url in browser ```http://app.dev.sas.com:[PORT]/```
- Install Bruno client using this link https://www.usebruno.com/
- Import shared-api-system from project repo

## Cloudinary setup for image storage

- Create an account on cloudinary https://cloudinary.com/users/login
- Add CLOUDINARY_URL key in .env and paste cloudinary url from cloudinary dashboard

## DB Setup

- Hit this url ```http://localhost:8080``` to open PHPmyAdmin
- Login with credentials added in .env file
- Run this command ```docker compose execc bin bash``` to open terminal inside docker container 
- Run this command ```php artisan migrate``` to migrate the database
- Run this command ```php artisan db:seed --class=BlogTableSeeder``` to populate blogs table (it'll insert 250 records)
