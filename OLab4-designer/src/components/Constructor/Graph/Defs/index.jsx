// @flow
/*
This component stores reusable html items that
are being used by such items like: arrows/nodes/backgrounds etc.

In turn these items are being copied in shadow roots or in css-styles by 'id'.
*/
import React from 'react';

import ArrowHead from './ArrowHead';
import BackgroundPattern from './BackgroundPattern';

import { processGraphConfigDefs } from './utils';

import type { IDefsProps, IDefsState } from './types';

export class Defs extends React.Component<IDefsProps, IDefsState> {
  state: IDefsState = {
    graphConfigDefs: [],
  };

  static defaultProps: IDefsProps = {
    gridSpacing: 36,
    edgeArrowSize: 6,
    gridDotSize: 2,
    renderDefs: () => null,
  };

  static getDerivedStateFromProps(nextProps: IDefsProps) {
    const graphConfigDefs = [];
    processGraphConfigDefs(nextProps.edgeTypes, graphConfigDefs);

    return {
      graphConfigDefs,
    };
  }

  render() {
    const { graphConfigDefs } = this.state;
    const {
      edgeArrowSize, gridSpacing, gridDotSize, renderDefs,
    } = this.props;

    return (
      <defs>
        {graphConfigDefs}
        <ArrowHead
          edgeArrowSize={edgeArrowSize}
        />
        <BackgroundPattern
          gridSpacing={gridSpacing}
          gridDotSize={gridDotSize}
        />
        {renderDefs && renderDefs()}
      </defs>
    );
  }
}

export default Defs;
