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

use \Marando\AstroDate\AstroDate;
use \Marando\AstroCoord\Geographic;

interface ITopographic {

  /**
   * Converts the geocentric coordinates of this instance to topographic
   * coordinates based on the provided geographic location and time
   *
   * @param  Geographic $geo  Topographic observation point
   * @param  AstroDate  $date Date of observation
   * @return static
   */
  public function topo(Geographic $geo, AstroDate $date);

  /**
   * Converts the topographic coordinates of this instance to geocentric
   * coordinates based on the provided geographic location and time
   *
   * @param  Geographic $geo  Topographic observation point
   * @param  AstroDate  $date Date of observation
   * @return static
   */
  public function geocentr(Geographic $geo, AstroDate $date);
}
