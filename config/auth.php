<?php
return [
    'token_secret' => envOverload('TOKEN_SECRET', 'HLVFscA97YMRRlVyNMvueWIBIITX8Q11'),
    'token_algo' => 'sha512',
    'login_key_secret' => '83f47260dcb0fdb54965d0790a4db307c29d33816cc18c2f976f00cb1282e24267e8147a570eede40e82524130a065b0fa3990db7863a6c04169f12e33c82146',
    'web_token_ttl' => envOverload('WEB_TOKEN_TTL', 60),
    'web_refresh_token_ttl' => envOverload('WEB_REFRESH_TOKEN_TTL', 120),
    'mobile_token_ttl' => envOverload('MOBILE_TOKEN_TTL', 1440),
    'mobile_refresh_token_ttl' => envOverload('MOBILE_REFRESH_TOKEN_TTL', 10080),
    'token_grace_period' => envOverload('TOKEN_GRACE_PERIOD', 2),
];
