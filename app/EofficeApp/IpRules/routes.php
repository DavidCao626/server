<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
    ['ip-rules/access-control/list', 'getIpRulesList',[106]],
    ['ip-rules/access-control/{ip_rules_id}', 'getOneIpRules',[106]],
    ['ip-rules/access-control/add', 'addIpRules', 'post',[106]],
    ['ip-rules/access-control/{ip_rules_id}', 'editIpRules', 'put',[106]],
    ['ip-rules/access-control/{ip_rules_id}', 'deleteIpRules', 'delete',[106]],
];