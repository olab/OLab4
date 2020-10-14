// @flow
import VerticalTree from './vertical-tree';

describe('VerticalTree', () => {
  const snapToGrid = {
    edges: [],
    edgeTypes: {},
    nodes: [],
    selected: null,
    onCreateNode: jest.fn(),
    onCreateEdge: jest.fn(),
    onDeleteNode: jest.fn(),
    onDeleteEdge: jest.fn(),
    onSelectNode: jest.fn(),
    onSelectEdge: jest.fn(),
    onSwapEdge: jest.fn(),
    onUpdateNode: jest.fn(),
    zoomControlsRef: {
      current: document.createElement('div'),
    },
    ACTION_SAVE_MAP_TO_UNDO: jest.fn(),
    gridSpacing: 10,
  };

  describe('class', () => {
    it('is defined', () => {
      expect(VerticalTree).toBeDefined();
    });
  });

  describe('calculatePosition method', () => {
    it('adjusts the node position to be centered on a grid space', () => {
      const verticalTree = new VerticalTree(snapToGrid);
      const nodes = [
        { id: 'test', x: 9, y: 8 },
        { id: 'test1', x: 4, y: 7 },
      ];
      const nodesMap = {
        'key-test': {
          incomingEdges: [],
          outgoingEdges: [{
            source: 'test',
            target: 'test1',
          }],
          node: nodes[0],
        },
        'key-test1': {
          incomingEdges: [{
            source: 'test1',
            target: 'test',
          }],
          outgoingEdges: [],
          node: nodes[1],
        },
      };
      const newNodes = verticalTree.adjustNodes(nodes, nodesMap);
      const expected = [
        { id: 'test', x: 150, y: 150 },
        { id: 'test1', x: 150, y: 500 },
      ];
      expect(JSON.stringify(newNodes)).toEqual(JSON.stringify(expected));
    });

    it('does nothing when there is no nodeMap', () => {
      const verticalTree = new VerticalTree(snapToGrid);
      const nodes = [
        { id: 'test', x: 9, y: 8 },
      ];
      const newNodes = verticalTree.adjustNodes(nodes);
      const expected = [
        { id: 'test', x: 9, y: 8 },
      ];
      expect(JSON.stringify(newNodes)).toEqual(JSON.stringify(expected));
    });

    it('does nothing on disconnected nodes', () => {
      const verticalTree = new VerticalTree(snapToGrid);
      const nodes = [
        { id: 'test', x: 9, y: 8 },
      ];
      const nodesMap = {
        'key-test': {
          incomingEdges: [],
          outgoingEdges: [],
          node: nodes[0],
        },
      };
      const newNodes = verticalTree.adjustNodes(nodes, nodesMap);
      const expected = [
        { id: 'test', x: 9, y: 8 },
      ];
      expect(JSON.stringify(newNodes)).toEqual(JSON.stringify(expected));
    });

    it('uses a default nodeSize', () => {
      const verticalTree = new VerticalTree(snapToGrid);
      const nodes = [
        { id: 'test', x: 9, y: 8 },
        { id: 'test1', x: 4, y: 7 },
      ];
      const nodesMap = {
        'key-test': {
          incomingEdges: [],
          outgoingEdges: [{
            source: 'test',
            target: 'test1',
          }],
          node: nodes[0],
        },
        'key-test1': {
          incomingEdges: [{
            source: 'test1',
            target: 'test',
          }],
          outgoingEdges: [],
          node: nodes[1],
        },
      };

      const newNodes = verticalTree.adjustNodes(nodes, nodesMap);
      const expected = [
        { id: 'test', x: 150, y: 150 },
        { id: 'test1', x: 150, y: 500 },
      ];
      expect(JSON.stringify(newNodes)).toEqual(JSON.stringify(expected));
    });
  });
});
