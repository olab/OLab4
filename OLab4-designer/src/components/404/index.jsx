// @flow
import React from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@material-ui/core';

import Icon404 from '../../shared/assets/icons/not_found.svg';

import { Wrapper, Header, Text } from './styles';

const PageNotFound = () => (
  <Wrapper>
    <Icon404 />
    <Header>404 - Page Not Found</Header>
    <Text>
      The Page you are looking for might have been removed had
      <br />
      its name changed or is temporarily unavailable
    </Text>
    <Link to="/" replace className="link">
      <Button
        variant="outlined"
        color="primary"
        size="large"
        aria-label="Return to Home"
      >
        Return to Home
      </Button>
    </Link>
  </Wrapper>
);

export default PageNotFound;
