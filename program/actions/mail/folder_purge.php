<?php

/**
 +-----------------------------------------------------------------------+
 | This file is part of the Roundcube Webmail client                     |
 |                                                                       |
 | Copyright (C) The Roundcube Dev Team                                  |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Implement folder PURGE request                                      |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <roundcube@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

class rcmail_action_mail_folder_purge extends rcmail_action_mail_index
{
    // only process ajax requests
    protected static $mode = self::MODE_AJAX;

    /**
     * Request handler.
     *
     * @param array $args Arguments from the previous step(s)
     */
    public function run($args = [])
    {
        $rcmail       = rcmail::get_instance();
        $delimiter    = $rcmail->storage->get_hierarchy_delimiter();
        $trash_mbox   = $rcmail->config->get('trash_mbox');
        $junk_mbox    = $rcmail->config->get('junk_mbox');
        $trash_regexp = '/^' . preg_quote($trash_mbox . $delimiter, '/') . '/';
        $junk_regexp  = '/^' . preg_quote($junk_mbox . $delimiter, '/') . '/';

        // we should only be purging trash and junk (or their subfolders)
        if ($mbox == $trash_mbox || $mbox == $junk_mbox
            || preg_match($trash_regexp, $mbox) || preg_match($junk_regexp, $mbox)
        ) {
            $success = $rcmail->storage->clear_folder($mbox);

            if ($success) {
                $rcmail->output->show_message('folderpurged', 'confirmation');
                $rcmail->output->command('set_unread_count', $mbox, 0);
                self::set_unseen_count($mbox, 0);

                // set trash folder state
                if ($mbox === $trash_mbox) {
                    $rcmail->output->command('set_trash_count', 0);
                }

                if (!empty($_REQUEST['_reload'])) {
                    $rcmail->output->set_env('messagecount', 0);
                    $rcmail->output->set_env('pagecount', 0);
                    $rcmail->output->set_env('exists', 0);
                    $rcmail->output->command('message_list.clear');
                    $rcmail->output->command('set_rowcount', self::get_messagecount_text(), $mbox);
                    $rcmail->output->command('set_quota', self::quota_content(null, $mbox));
                }
            }
            else {
                self::display_server_error();
            }
        }

        $rcmail->output->send();
    }
}