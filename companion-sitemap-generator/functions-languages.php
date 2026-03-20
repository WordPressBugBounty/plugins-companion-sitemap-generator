<?php

/**
 * Multilingual Integration Functions
 *
 * This file contains functions regarding multilingual integration
 *
 *
 * @see     https:/wijzijnqreative.nl/url-to-be-added/
 * @version 4.6.0
 */

 // NOTE: All of these are broken right now, will fix in next update

 // Is multilingual
 function csg_is_multilingual() {
	// return ( in_array( 'polylang/polylang.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) ? true : false;
	return false;
 }
 // List of all languages
 function csg_languages() {
	return [];
	// return pll_languages_list();
 }
 // Default language
 function csg_default_language() {
	// return pll_default_language();
 }
 // Get ID of stranslation for post
 function csg_post_translation_id( $id, $lang ) {
	// return pll_get_post( $id, $lang );
 }
 // Get ID of stranslation for term / category
 function csg_term_translation_id( $id, $lang ) {
	// return pll_get_term( $id, $lang );
 }
 function csg_get_term_language( $id ) {
	// return pll_get_term_language( $id );
 }
