// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { BackgroundPattern } from '.';

describe('<BackgroundPattern />', () => {
  let output = {};

  beforeEach(() => {
    output = shallow(<BackgroundPattern />);
  });

  describe('component is rendered', () => {
    it('rendered', () => {
      expect(output.getElement()).not.toBeNull();

      expect(output.props().id).toEqual('grid');
      expect(output.props().width).toEqual(36);
      expect(output.props().width).toEqual(36);
    });
  });
});
