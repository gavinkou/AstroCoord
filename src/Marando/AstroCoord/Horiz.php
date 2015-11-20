<?php

/*
 * Copyright (C) 2015 Ashley Marando
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
use \Marando\Units\Distance;

/**
 * Represents horizontal (alt/az) coordinates
 *
 * @property Angle $alt  Altitude
 * @property Angle $az   Azimuth
 * @property Angle $dist Observer to target distance
 */
class Horiz {

  use Traits\CopyTrait,
      Traits\HorizFormat;

  //----------------------------------------------------------------------------
  // Constants
  //----------------------------------------------------------------------------

  /**
   * Default Format:
   * α h -69°38'48".697, A 087°21'04".049
   */
  const FORMAT_DEFAULT = '\h Hd°Hm\'Hs".Hu, \A Ad°Am\'As".Au';

  /**
   * Full Format:
   * h -69°38'48".697, A 087°21'04".049, 0.768 AU
   */
  const FORMAT_FULL = '\h Hd°Hm\'Hs".Hu, \A Ad°Am\'As".Au, Da';

  /**
   * Degree Format:
   * h -69.64686°, A 087.35112°
   */
  const FORMAT_DEGREES = '\h H°, \A A°';

  /**
   * Spaced Format:
   * h -69 38 48.697, A 087 21 04.049
   */
  const FORMAT_SPACED = '\h Hd Hm Hs.Hu, \A Ad Am As.Au';

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new horizontal coordinate
   *
   * @param Angle    $alt  Altitude, 0° = Horizon
   * @param Angle    $az   Azimuth, 0° = North, 90° = East
   * @param Distance $dist Observer to target distance
   */
  public function __construct(Angle $alt, Angle $az, Distance $dist = null) {
    // Set position and distance
    $this->setPosition($alt, $az);
    $this->setDistance($dist);

    // Set default string format
    $this->format = static::FORMAT_DEFAULT;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Altitude
   * @var Angle
   */
  protected $alt;

  /**
   * Azimuth
   * @var Angle
   */
  protected $az;

  /**
   * Observer to target distance
   * @var Distance
   */
  protected $dist;

  public function __get($name) {
    switch ($name) {
      case 'alt':
      case 'az':
        return $this->{$name};
    }
  }

  public function __set($name, $value) {
    switch ($name) {
      case 'alt':
        return $this->setPosition($value, $this->az);

      case 'az':
        return $this->setPosition($this->alt, $value);

      case 'dist':
        return $this->setDistance($value);
    }
  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Sets the altitude and azimuth of this instance
   *
   * @param  Angle  $alt Altitude, 0° = Horizon
   * @param  Angle  $az  Azimuth, 0° = North, 90° = East
   * @return static
   */
  public function setPosition(Angle $alt, Angle $az) {
    $this->alt = $alt;
    $this->az  = $az;

    return $this;
  }

  /**
   * Sets the target to observer distance
   *
   * @param  Distance $dist
   * @return static
   */
  public function setDistance(Distance $dist) {
    $this->dist = $dist;

    return $this;
  }

  /**
   * Sets the topographic observation point of this instance
   *
   * @param  Geo    $geo
   * @return static
   */
  //public function setTopo(Geo $geo) {
  //throw new Exception('Not implemented');

  /**
   * 1. Convert this from Alt/Az Topo to Geocentric RA/Dec
   * 2. Convert Geocentric to topo Alt/Az
   */
  //$this->topo = $geo;
  //return $this;
  //}
  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return $this->format($this->format);
  }

}
