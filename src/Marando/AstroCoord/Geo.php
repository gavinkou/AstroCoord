<?php

/*
 * Copyright (C) 2015 ashley
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Marando\AstroCoord;

use \Marando\Units\Angle;

/**
 * Represents a geographic location
 *
 * @property Angle $lat Latitude
 * @property Angle $lon Longitude
 */
class Geo {
  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new geographic location
   *
   * @param Angle $lat Latitude
   * @param Angle $lon Longitude, West negative
   */
  public function __construct(Angle $lat, Angle $lon) {
    $this->lat = $lat->norm(-180, 180);
    $this->lon = $lon->norm(-90, 90);
  }

  // // // Static

  /**
   * Creates a new geographic location from values expressed as degrees
   *
   * @param  float  $lat Latitude, degrees
   * @param  float  $lon Longitude, degrees West negative
   * @return static
   */
  public static function deg($lat, $lon) {
    return new static(Angle::deg($lat), Angle::deg($lon));
  }

  /**
   * Creates a new geographic location from values expressed as radians
   *
   * @param  float  $lat Latitude, radians
   * @param  float  $lon Longitude, radians West negative
   * @return static
   */
  public static function rad($lat, $lon) {
    return new static(Angle::rad($lat), Angle::rad($lon));
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Latitude
   * @var Angle
   */
  protected $lat;

  /**
   * Longitude, West negative
   * @var Angle
   */
  protected $lon;

  public function __get($name) {
    switch ($name) {
      case 'lat':
      case 'lon':
        return $this->{$name};
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Returns true if the latitude is North
   * @return bool
   */
  public function isN() {
    return $this->lat->deg >= 0;
  }

  /**
   * Returns true if the longitude is West
   * @return bool
   */
  public function isW() {
    return $this->lon->deg <= 0;
  }

  /**
   * Returns true if the latitude is South
   * @return bool
   */
  public function isS() {
    return $this->lat->deg <= 0;
  }

  /**
   * Returns true if the longitude is East
   * @return bool
   */
  public function isE() {
    return $this->lon->deg >= 0;
  }

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    // Figure out cardinal directions
    $latDir = $this->isN() ? 'N' : 'S';
    $lonDir = $this->isW() ? 'W' : 'E';

    // Get the lat/lon as positive values
    $lat = $this->lat->deg >= 0 ? $this->lat : $this->lat->copy()->negate();
    $lon = $this->lon->deg >= 0 ? $this->lon : $this->lon->copy()->negate();

    return "$lat $latDir, $lon $lonDir";
  }

}
