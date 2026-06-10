<?php

/*
|--------------------------------------------------------------------------
| Domain → Database Mapping
|--------------------------------------------------------------------------
|
| Add one entry per shop. The key is the domain (or subdomain) the shop
| is accessed from; the value is the MySQL database name for that shop.
|
| Example:
|   'shop1.lmucpos.lk' => 'lmuc_pos_shop1',
|   'shop2.lmucpos.lk' => 'lmuc_pos_shop2',
|
| The DB_DATABASE value in .env is used as the fallback when the domain
| is not found in this list (useful for local/dev access).
|
*/

return [

    'domains' => [
        // 'shop1.lmucpos.lk' => 'lmuc_pos_shop1',
        // 'shop2.lmucpos.lk' => 'lmuc_pos_shop2',
        // 'localhost'         => 'lmuc_pos_sinhala',
    ],

];
