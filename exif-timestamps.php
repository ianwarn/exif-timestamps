<?php
/**
 * Plugin Name: EXIF timestamps
 * Description:  Fix up WordPress to read DateTimeOriginal from EXIF and update the post_date.
 * Version: 1.0.1
 * Author: Scott Fennell/Ian Warn
 * Author URI: http://scottfennell.org/2014/05/28/get-wordpress-to-use-image-exif-date-as-post_date-for-attachments/
 * License: GPL2
 */

/**
 * Return the EXIF date for a jpeg attachment post.
 * 
 * @return string The Exif time on which the image was taken.
 */
 function sjf_deh_get_exif_date( $attachment_ID ) {
 	
 	// Grab the current post.
 	$post = get_post( $attachment_ID );
 	// If this isn't an attachment, bail.
 	if( $post->post_type != 'attachment' ) { return false; }
 	// If this isn't an jpeg, bail.
	$mime = get_post_mime_type();
 	if( $mime != 'image/jpeg' ) { return false; }
 	// Get the path to the attachment file.
 	$path = get_attached_file( $post->ID );
 	if( empty( $path ) ) { return false; }
 	// Get the exif data.
	$exif = exif_read_data( $path );
	
	// Get the exif time.
	if( isset( $exif[ 'DateTimeOriginal' ] ) ) {
		$exif_time = $exif[ 'DateTimeOriginal' ];
	
	// If there is no exif time, bail.
	} else {
		return false;
	}
	// Convert the time to Unix time.
	$unix_time = strtotime( $exif_time );
	// If the time seems like the Unix epoch, which it might be if strtotime gets an oddball value, bail.	
	if( stristr( '1970-01-01', $exif_time ) ) { return false; }
	// Convert the time back to the format used by WordPress $post->post_date.
	$time = date( 'Y-m-d H:i:s', $unix_time );
	return $time;
 
 }
 
/**
 * Hooks onto an attachment post just after it's created and alters the post date to reflect the EXIF time.
 * 
 * @param  int $attachment_ID The ID of the post we're altering.
 * @return int The ID of the post we're altering.
 */
function sjf_deh_exif_add( $attachment_ID ) {
    
    // Grab the exif date for this image
	$time = sjf_deh_get_exif_date( $attachment_ID );
	
	// If we can't grab the exif date, bail.
	if( !$time || empty( $time ) ) { return $attachment_ID; }
	// Build up an array of post attributes for updating the post.
	$update = array(
		'ID' => $attachment_ID,
		'post_date' => $time,
	);
	// Update the post.
	wp_update_post( $update );
    return $attachment_ID;
}
add_filter( 'add_attachment', 'sjf_deh_exif_add' );
