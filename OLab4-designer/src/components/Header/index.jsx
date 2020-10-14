// @flow
import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { LinearProgress } from '@material-ui/core';

import NavigationBar from './NavigationBar';
import LogoIcon from '../../shared/assets/icons/olab4_logo.svg';

import type { IHeaderProps } from './types';

import { Logo, HeaderWrapper, FakeProgress } from './styles';

const Header = ({ isDataFetching }: IHeaderProps) => (
  <HeaderWrapper>
    <div>
      <Link to="/" className="route-link">
        <Logo>
          <LogoIcon />
          <h1>OLab4</h1>
        </Logo>
      </Link>
      <NavigationBar />
    </div>
    {isDataFetching ? <LinearProgress /> : <FakeProgress />}
  </HeaderWrapper>
);

const mapStateToProps = ({
  user, map, mapDetails, scopedObjects, counterGrid,
}) => ({
  isDataFetching: user.isFetching
    || map.isFetching
    || map.isUpdating
    || map.isDeleting
    || mapDetails.isFetching
    || counterGrid.isFetching
    || scopedObjects.isCreating
    || scopedObjects.isUpdating
    || scopedObjects.isDeleting,
});

export default connect(mapStateToProps)(Header);
