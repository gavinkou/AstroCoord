<?php

thi s should just be time



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
use \Marando\Units\Time;

class RightAscension {

  //----------------------------------------------------------------------------
  // Constructors
  //----------------------------------------------------------------------------

  public function __construct(Angle $angle) {

  }

  // // // Static

  public static function hms() {

  }

  public static function angle() {

  }

  public static function deg() {

  }

  public static function rad() {

  }

  public static function time() {

  }

  //----------------------------------------------------------------------------
  // Properties
  //----------------------------------------------------------------------------

  /**
   * Angle representation of this right ascension
   * @var Angle
   */
  protected $angle;

  public function __get($name) {

  }

  public function __set($name, $value) {

  }

  //----------------------------------------------------------------------------
  // Functions
  //----------------------------------------------------------------------------

  public function norm() {
    $this->angle->norm(0, 360);
  }

  public function

}
