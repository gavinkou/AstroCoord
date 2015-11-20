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

namespace Marando\AstroCoord\Traits;

trait EquatFormat {

  /**
   * String format for the instance
   * @var string
   */
  protected $format;

  public function format($format) {
    // Store current format
    $this->format = $format;

    // Frame with time format
    if (preg_match('/F{(.*)}/', $format, $m)) {
      if ($this->apparent) {
        // Apparent, show epoch in time format
        $epoch  = $this->epoch->toDate()->format($m[1]);
        $format = preg_replace('/(F{.*})/', $epoch, $format);
      }
      else {
        // Astrometric, show frame
        $frame  = str_replace('R', '\R', $this->frame);
        $format = preg_replace('/(F{.*})/', $frame, $format);
      }
    }


    if (strstr($format, 'Rh')) {
      $repl   = strstr($format, '+Rh') ? '+Rh' : 'Rh';
      $fmt    = strstr($format, '+Rh') ? '%+03d' : '%02d';
      $str    = sprintf($fmt, $this->ra->h);
      $format = str_replace($repl, $str, $format);
    }

    if (strstr($format, 'Rm')) {
      $str    = sprintf('%02d', $this->ra->m);
      $format = str_replace('Rm', $str, $format);
    }

    if (strstr($format, 'Rs')) {
      $str    = sprintf('%02d', $this->ra->s);
      $format = str_replace('Rs', $str, $format);
    }

    if (strstr($format, 'Ru')) {
      $str    = sprintf('%02.3f', $this->ra->micro);
      $str    = str_replace('0.', '', $str);
      $format = str_replace('Ru', $str, $format);
    }

    if (strstr($format, 'R')) {
      $repl   = strstr($format, '+R') ? '+R' : 'R';
      $fmt    = strstr($format, '+R') ? '%+03.5f' : '%03.5f';
      $str    = sprintf($fmt, $this->ra->toAngle()->deg);
      $format = str_replace($repl, $str, $format);
      $format = str_replace("\\$str", 'R', $format);
    }

    // // //

    if (strstr($format, 'Dau')) {
      $str    = sprintf('%02.3f', $this->dist->au);
      $format = str_replace('Dau', "$str AU", $format);
    }

    if (strstr($format, 'Da')) {
      if ($this->dist->au < 1) {
        $u   = 'km';
        $str = sprintf('%02.3f', $this->dist->km);
      }
      else if ($this->dist->pc > 1) {
        $u   = 'pc';
        $str = sprintf('%02.3f', $this->dist->pc);
      }
      else {
        $u   = 'au';
        $str = sprintf('%02.3f', $this->dist->au);
      }
      $str = number_format($str, 3, '.', ',');

      $format = str_replace('Da', "$str $u", $format);
    }

    // // //

    if (strstr($format, 'Dd')) {
      $repl   = strstr($format, '+Dd') ? '+Dd' : 'Dd';
      $fmt    = strstr($format, '+Dd') ? '%+03d' : '%02d';
      $str    = sprintf($fmt, $this->dec->d);
      $format = str_replace($repl, $str, $format);
    }

    if (strstr($format, 'Dm')) {
      $str    = sprintf('%02d', abs($this->dec->m));
      $format = str_replace('Dm', $str, $format);
    }

    if (strstr($format, 'Ds')) {
      $str    = sprintf('%02d', abs($this->dec->s));
      $format = str_replace('Ds', $str, $format);
    }

    if (strstr($format, 'Du')) {
      $str    = sprintf('%02.3f', abs(intval($this->dec->s) - $this->dec->s));
      $str    = str_replace('0.', '', $str);
      $format = str_replace('Du', $str, $format);
    }


    if (strstr($format, 'D')) {
      $repl   = strstr($format, '+D') ? '+D' : 'D';
      $fmt    = strstr($format, '+D') ? '%+09.5f' : '%09.5f';
      $str    = sprintf($fmt, $this->dec->deg);
      $format = str_replace($repl, $str, $format);
      $format = str_replace("\\$str", 'D', $format);
    }

    // // //

    $format = str_replace("\\", '', $format);
    return $format;
  }

}
