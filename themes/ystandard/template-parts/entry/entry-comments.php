<?php
/**
 * コメントテンプレート
 *
 * @package ystandard
 * @author yosiakatsuki
 * @license GPL-2.0+
 */

if ( ! ys_is_amp() && ( comments_open() || get_comments_number() ) ) {
	comments_template();
}