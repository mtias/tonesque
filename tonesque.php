<?php
/*
Plugin Name: Tonesque
Plugin URI: http://automattic.com/
Description: Class to grab an average color representation from an image.
Version: 1.0
Author: Matias Ventura
Author URI: http://matiasventura.com
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class Tonesque {

	private $image = '';
	private $color = '';

	function __construct( $image ) {
		require_lib( 'class.color' );
		$this->image = esc_url_raw( $image );
	}

	/**
	 *
	 * Construct object from image.
	 *
	 * @param optional $type (hex, rgb, hsl)
	 * @return color as a string formatted as $type
	 *
 	 */
	function color( $type = 'hex' ) {
		// Bail if there is no image to work with
	 	if ( ! $this->image )
			return false;

	 	$image = trim( $this->image );

	 	// Grab the extension
		$file = strtolower( pathinfo( $image, PATHINFO_EXTENSION ) );
		$file = explode( '?', $file );
		$file = $file[ 0 ];

		switch ( $file ) {
			case 'gif' :
				$img = imagecreatefromgif( $image );
				break;
			case 'png' :
				$img = imagecreatefrompng( $image );
				break;
			case 'jpg' :
			case 'jpeg' :
				$img = imagecreatefromjpeg( $image );
				break;
			default:
				return false;
		}

		// Finds dominant color
		$color = self::grab_color( $img );
		// Passes value to Color class
		$color = self::get_color( $color, $type );
		return $color;
	}

	/**
	 *
	 * Finds the average color of the image based on five sample points
	 *
	 * @param $image
	 * @return array() with rgb color
	 *
 	 */
	function grab_color( $image ) {
		$img = $image;

		$height = imagesy( $img );
		$width  = imagesx( $img );

		// Sample five points in the image
		// Based on rule of thirds and center
		$topy    = round( $height / 3 );
		$bottomy = round( ( $height / 3 ) * 2 );
		$leftx   = round( $width / 3 );
		$rightx  = round( ( $width / 3 ) * 2 );
		$centery = round( $height / 2 );
		$centerx = round( $width / 2 );

		// Cast those colors into an array
		$rgb = array(
			imagecolorat( $img, $leftx, $topy ),
			imagecolorat( $img, $rightx, $topy ),
			imagecolorat( $img, $leftx, $bottomy ),
			imagecolorat( $img, $rightx, $bottomy ),
			imagecolorat( $img, $centerx, $centery ),
		);

		// Process the color points
		// Find the average representation
		for ( $i = 0; $i <= count( $rgb ) - 1; $i++ ) {
			$r[ $i ] = ( $rgb[ $i ] >> 16 ) & 0xFF;
			$g[ $i ] = ( $rgb[ $i ] >> 8 ) & 0xFF;
			$b[ $i ] = $rgb[ $i ] & 0xFF;

			$red = round( array_sum( $r ) / 5 );
			$green = round( array_sum( $g ) / 5 );
			$blue = round( array_sum( $b ) / 5 );
		}

		// The average color of the image as rgb array
		$color = array(
			'r' => $red,
			'g' => $green,
			'b' => $blue,
		);

		return $color;
	}

	/**
	 *
	 * Get a Color object using /lib class.color
	 * Convert to appropiatte type
	 *
	 * @return string
	 *
 	 */
	function get_color( $color, $type ) {
		$c = new Color( $color, 'rgb' );
		$this->color = $c;

		switch ( $type ) {
			case 'rgb' :
				$color = implode( $c->toRgbInt(), ',' );
				break;
			case 'hex' :
				$color = $c->toHex();
				break;
			case 'hsv' :
				$color = implode( $c->toHsvInt(), ',' );
				break;
			default:
				return $color = $c->toHex();
		}

		return $color;
	}

	/**
	 *
	 * Checks contrast against main color
	 * Gives either black or white for using with opacity
	 *
	 * @return string
	 *
 	 */
	function contrast() {
		$c = $this->color->getMaxContrastColor();
		return implode( $c->toRgbInt(), ',' );
	}
};