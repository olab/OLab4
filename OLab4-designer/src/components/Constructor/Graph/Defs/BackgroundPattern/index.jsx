// @flow
/*
This <pattern /> locates in defs section.
It is being used for background pattern;

For more info about <pattern />:
https://developer.mozilla.org/en-US/docs/Web/SVG/Element/pattern
*/
import React, { Component } from 'react';
import Circle from './Circle';

import { PATTERN_ID } from './config';

import type { IBackgroundPatternProps } from './types';

export class BackgroundPattern extends Component<IBackgroundPatternProps> {
  static defaultProps: IBackgroundPatternProps = {
    gridSpacing: 36,
    gridDotSize: 2,
  }

  render() {
    const { gridSpacing, gridDotSize } = this.props;

    return (
      <pattern
        id={PATTERN_ID}
        key={PATTERN_ID}
        width={gridSpacing}
        height={gridSpacing}
        patternUnits="userSpaceOnUse"
      >
        <Circle
          gridDotSize={gridDotSize}
        />
      </pattern>
    );
  }
}

export default BackgroundPattern;
