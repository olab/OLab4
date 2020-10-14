// @flow
/*
This component draws background of the graph;
Here you can set type and view of the Background.
It can be e.x. dotted/grid etc.
All patterns are being grabbed from Defs section by id.

Also here is set size of the grid. Now it is 40960px.
*/
import React, { Component } from 'react';

import type { IBackgroundProps } from './types';

export class Background extends Component<IBackgroundProps> {
  static defaultProps: IBackgroundProps = {
    backgroundFillId: '#grid',
    gridSize: 40960,
  }

  render() {
    const { gridSize, backgroundFillId } = this.props;

    return (
      <rect
        className="background"
        x={-gridSize / 4}
        y={-gridSize / 4}
        width={gridSize}
        height={gridSize}
        fill={`url(${backgroundFillId})`}
      />
    );
  }
}

export default Background;
