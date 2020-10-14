// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { Background } from '.';

describe('<Background />', () => {
  let output = {};

  beforeEach(() => {
    output = shallow(<Background />);
  });

  describe('component is rendered', () => {
    it('rendered', () => {
      expect(output.getElement()).not.toBeNull();
      expect(output.props().x).toEqual(-10240);
      expect(output.props().y).toEqual(-10240);
      expect(output.props().width).toEqual(40960);
      expect(output.props().height).toEqual(40960);
    });
  })
});
