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

use \Marando\AstroDate\Epoch;

/**
 * Represents an astronomical coordinate reference frame
 *
 * @property string $name    Name of the reference frame
 * @property Epoch  $equinox Equniox of the reference frame
 */
class Frame {

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  protected function __construct($frame, Epoch $equinox = null) {
    $this->frame   = $frame;
    $this->equinox = $equinox;
  }

  // // // Static

  /**
   * Represents the International Celestial Reference Frame, IRCF
   * @return static
   */
  public static function ICRF() {
    return new static('ICRF', Epoch::J2000());
  }

  /**
   * Represents the Fifth Fundamental Catalogue, FK5
   *
   * @param  Epoch  $equinox An optional frame epoch, default is J2000.0
   * @return static
   */
  public static function FK5(Epoch $equinox = null) {
    return new static('FK5', $equinox ? $equinox : Epoch::J2000());
  }

  /**
   * Represents the Fourth Fundamental Catalogue, FK4
   *
   * @param  Epoch  $equinox An optional frame epoch, default is B1950.0
   * @return static
   */
  public static function FK4(Epoch $equinox = null) {
    return new static('FK4', $equinox ? $equinox : Epoch::B1950());
  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Name of the reference frame
   * @var string
   */
  protected $frame;

  /**
   * Equniox of the reference frame
   * @var Epoch
   */
  protected $equinox;

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  /**
   * Represents this instance as a string
   * @return string
   */
  public function __toString() {
    return "$this->frame/$this->equinox";
  }

}
