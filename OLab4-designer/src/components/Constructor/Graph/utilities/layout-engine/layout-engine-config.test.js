// @flow
import LayoutEngines from './layout-engine-config';

describe('LayoutEngineConfig', () => {
  describe('class', () => {
    it('is defined', () => {
      expect(LayoutEngines).toBeDefined();
      expect(LayoutEngines.None).toBeDefined();
      expect(LayoutEngines.SnapToGrid).toBeDefined();
      expect(LayoutEngines.VerticalTree).toBeDefined();
    });
  });
});
