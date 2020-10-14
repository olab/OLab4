// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { Constructor } from '.';

describe('<Constructor />', () => {
  let output = {};
  beforeEach(() => {
    output = shallow(<Constructor />);
  });

  describe('render method', () => {
    it('renders', () => {
      expect(output.getElement()).not.toBeNull();
    });
  });

  describe('changeIfFullScreen method', () => {
    it('should change state of component', () => {
      output.instance().changeIfFullScreen(true);
      expect(output.state().isFullScreen).toEqual(true);
    });
  });

  describe('toggleFullScreen method', () => {
    it('should toggle state of component', () => {
      output.instance().toggleFullScreen();
      expect(output.state().isFullScreen).toEqual(true);
    });
  });
});
