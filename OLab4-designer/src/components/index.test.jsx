// @flow
import React from 'react';

import { shallow } from 'enzyme';
import { App } from '.';

describe('<App />', () => {
  let output = {};
  let isAuth;
  beforeEach(() => {
    isAuth = false;
    output = shallow(
      <App
        isAuth={isAuth}
      />,
    );
  });

  it('renders successfully', () => {
    expect(output.getElement()).not.toBeNull();
  });
});
