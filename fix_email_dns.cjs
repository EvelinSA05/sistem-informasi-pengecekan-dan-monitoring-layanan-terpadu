const fs = require('fs');

// 1. RegisteredUserController.php
let c1 = fs.readFileSync('app/Http/Controllers/Auth/RegisteredUserController.php', 'utf8');
c1 = c1.replace(/'email' => 'required\|string\|lowercase\|email\|max:150\|unique:users,email',/g, "'email' => 'required|string|lowercase|email:rfc,dns|max:150|unique:users,email',");
fs.writeFileSync('app/Http/Controllers/Auth/RegisteredUserController.php', c1);

// 2. UserController.php
let c2 = fs.readFileSync('app/Http/Controllers/Admin/UserController.php', 'utf8');
c2 = c2.replace(/'email'    => 'required\|string\|lowercase\|email\|max:150\|unique:users,email',/g, "'email'    => 'required|string|lowercase|email:rfc,dns|max:150|unique:users,email',");
// The array format in update()
c2 = c2.replace(/,\n\s+'email',\n/g, ",\n                'email:rfc,dns',\n");
fs.writeFileSync('app/Http/Controllers/Admin/UserController.php', c2);

// 3. ProfileUpdateRequest.php
let c3 = fs.readFileSync('app/Http/Requests/ProfileUpdateRequest.php', 'utf8');
c3 = c3.replace(/,\n\s+'email',\n/g, ",\n                'email:rfc,dns',\n");
fs.writeFileSync('app/Http/Requests/ProfileUpdateRequest.php', c3);

console.log('Fixed email validation to include DNS check');
