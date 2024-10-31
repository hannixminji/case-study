<?php

ini_set('session.cookie_lifetime', '0'     );
ini_set('session.cookie_secure'  , '0'     );
ini_set('session.cookie_httponly', '1'     );
ini_set('session.cookie_samesite', 'Lax'   );
ini_set('session.use_strict_mode', '1'     );
ini_set('session.sid_length'     , '32'    );
ini_set('session.hash_function'  , 'sha256');

session_start();
