// @flow
import * as dagre from 'dagre';
import type { Node as NodeType } from '../../Node/types';
import SnapToGrid from './snap-to-grid';

type MapNodes = {
  [key: string]: any;
}
class VerticalTree extends SnapToGrid {
  // eslint-disable-next-line class-methods-use-this
  adjustNodes(nodes: Array<NodeType>, nodesMap?: MapNodes): Array<NodeType> {
    const size = 300 * 1.7;
    const g = new dagre.graphlib.Graph();
    g.setGraph({});
    g.setDefaultEdgeLabel(() => ({}));

    nodes.forEach((node) => {
      if (!nodesMap) {
        return;
      }

      const nodeId = node.id;
      const nodeKeyId = `key-${nodeId}`;
      const nodesMapNode = nodesMap[nodeKeyId];

      // prevent disconnected nodes from being part of the graph
      if (nodesMapNode.incomingEdges.length === 0 && nodesMapNode.outgoingEdges.length === 0) {
        return;
      }
      g.setNode(nodeKeyId, { width: size, height: size });
      nodesMapNode.outgoingEdges.forEach((edge) => {
        g.setEdge(nodeKeyId, `key-${edge.target}`);
      });
    });

    dagre.layout(g);

    g.nodes().forEach((gNodeId: string) => {
      const nodesMapNode = nodesMap[gNodeId];

      // gNode is the dagre representation
      const gNode = g.node(gNodeId);

      nodesMapNode.node.x = gNode.x;
      nodesMapNode.node.y = gNode.y;
    });
    return nodes;
  }
}

export default VerticalTree;
