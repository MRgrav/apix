# AUTH

## User Login
* api: /api/auth/login
* method: POST
* payloads: 
```
{
    phone: number;
    password: string;
}
```


## User Registration
### api: /api/auth/registration
### method: POST
### payloads: 
```
{
    name: string;
    phone: number;
    password: string;
    confirmed: string;
    email: string;
    is_nri: boolean;
    country_code: int;
}
```
### response:
* 201:
```
{
    message: string;
    user_id: number;
}
```
* 400:
```
{
    message: string;
    errors: string[];
}
```
* 500:
```
{
    message: string;
    error: string[];
}
```