// @flow
import type { Node as NodeType } from '../../Node/types';
import type { IGraphViewProps } from '../../GraphView/types';

export type IPosition = {
  x: number;
  y: number;
  [key: string]: any;
};

class LayoutEngine {
  constructor(graphViewProps: IGraphViewProps) {
    this.graphViewProps = graphViewProps;
  }

  graphViewProps: IGraphViewProps;

  // eslint-disable-next-line class-methods-use-this
  calculatePosition(node: IPosition) {
    return node;
  }

  // eslint-disable-next-line class-methods-use-this
  adjustNodes(nodes: Array<NodeType>): Array<NodeType> {
    for (let i = 0; i < nodes.length; i += 1) {
      const node = nodes[i];
      const position = this.calculatePosition({
        x: node.x || 0,
        y: node.y || 0,
      });

      node.x = position.x;
      node.y = position.y;
    }

    return nodes;
  }
}

export default LayoutEngine;
