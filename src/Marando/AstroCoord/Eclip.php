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
 * Represents ecliptic coordinates
 *
 * @property Angle    $lon  Ecliptic longitude, λ
 * @property Angle    $lat  Ecliptic latitude, β
 * @property Distance $dist Observer to target distance
 */
class Eclip {

  use Traits\CopyTrait,
      Traits\EclipFormat;

  //----------------------------------------------------------------------------
  // Constants
  //----------------------------------------------------------------------------

  /**
   * Default Format:
   * λ 195°41'03".276, β +07°46'10".325
   */
  const FORMAT_DEFAULT = 'λ Ld°Lm\'Ls".Lu, β +Bd°Bm\'Bs".Bu';

  /**
   * Full Format:
   * λ 195°41'03".276, β +07°46'10".325, 0.768 AU
   */
  const FORMAT_FULL = 'λ Ld°Lm\'Ls".Lu, β +Bd°Bm\'Bs".Bu, Da';

  /**
   * Degree Format:
   * λ 195.68424, β +07.76953
   */
  const FORMAT_DEGREES = 'λ L°, β +B°';

  /**
   * Spaced Format:
   * λ 195 195.68424 03.276, β +07 46 10.325
   */
  const FORMAT_SPACED = 'λ Ld Lm Ls.Lu, β +Bd Bm Bs.Bu';

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  /**
   * Creates a new Ecliptic coordinate
   *
   * @param Angle    $lon  Ecliptic longitude
   * @param Angle    $lat  Ecliptic latitude
   * @param Distance $dist Observer to target distance
   */
  public function __construct(Angle $lon, Angle $lat, Distance $dist = null) {
    // Set position and distance
    $this->setPosition($lon, $lat);
    $this->setDistance($dist);

    // Set default string format
    $this->format = static::FORMAT_DEFAULT;
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Ecliptic longitude
   * @var Angle
   */
  protected $lon;

  /**
   * Ecliptic latitude
   * @var Angle
   */
  protected $lat;

  /**
   * Observer to target distance
   * @var Distance
   */
  protected $dist;

  public function __get($name) {
    switch ($name) {
      case 'lon':
      case 'lat':
      case 'dist':
        return $this->{$name};
    }
  }

  public function __set($name, $value) {
    switch ($name) {
      case 'lon':
        return $this->setPosition($value, $this->lat);

      case 'lat':
        return $this->setPosition($this->lon, $value);

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
   * @param  Angle  $lon Ecliptic longitude
   * @param  Angle  $lat Ecliptic latitude
   * @return static
   */
  public function setPosition(Angle $lon, Angle $lat) {
    $this->lon = $lon;
    $this->lat = $lat;

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

  // // // Overrides

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return $this->format($this->format);
  }

}
