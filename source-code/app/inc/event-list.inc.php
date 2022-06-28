<?php if (!defined('APP_VERSION')) die("Yo, what's up?"); ?>
<?php 
/**
 * This file is not being used in any place
 * Has been created to take a list of system events
 *
 *
 * 
 *
 * @event  router.map
 * @desc   Being triggered just before route match
 *         Usefull to add custom routes for the plugins
 * @param  string: Name of the global variable for the router 
 *         See: /app/core/App.php:defineController()
 * 
 * @event  cron.add
 * @desc   Add new cron task
 *
 *
 *
 *
 *
 * @event  user.signup
 * @desc   Event is being fired when new user registers
 * @param  \UserModel $User New User model data
 *
 *
 * @event  user.signin
 * @desc   Event is being fired when someone logs into the system successfully
 * @param  \UserModel $User User model data
 *
 *
 * @event  user.signout
 * @desc   Event is being fired when someone logs out of system successfully
 * @param  \UserModel $User User model data
 *
 *
  @event  theme.load
 * @desc  After active theme loaded
 */

