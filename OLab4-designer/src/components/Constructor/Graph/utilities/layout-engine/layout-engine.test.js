// @flow
import LayoutEngine from './layout-engine';

describe('LayoutEngine', () => {
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
      expect(LayoutEngine).toBeDefined();
    });
  });

  describe('calculatePosition method', () => {
    it('returns the node with no changes', () => {
      const layoutEngine = new LayoutEngine(snapToGrid);
      const position = { x: 1, y: 2 };
      const newPosition = layoutEngine.calculatePosition(position);
      expect(newPosition).toEqual(position);
    });
  });
});
