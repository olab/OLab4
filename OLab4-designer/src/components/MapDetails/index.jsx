// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import {
  Paper, Tabs, Tab, Button,
} from '@material-ui/core';

import Appearance from './Appearance';
import BasicDetails from './BasicDetails';
import ContentDetails from './ContentDetails';
import AdvancedDetails from './AdvancedDetails';

import * as mapDetailsActions from '../../redux/mapDetails/action';

import { ACCESS } from './config';

import type { MapDetails } from '../../redux/mapDetails/types';
import type { MapDetailsProps as IProps, MapDetailsState as IState } from './types';

import styles, {
  TabContainer, Container, ScrollingContainer, Title, Header,
} from './styles';

class AdvancedNodeEditor extends PureComponent<IProps, IState> {
  numberTab: number = 0;

  constructor(props) {
    super(props);
    const {
      mapIdUrl,
      mapDetails,
      ACTION_GET_MAP_DETAILS_REQUESTED,
    } = this.props;
    const isPageRefreshed = mapIdUrl && !mapDetails.id;

    if (isPageRefreshed) {
      ACTION_GET_MAP_DETAILS_REQUESTED(mapIdUrl);
    }

    this.state = { ...mapDetails };
  }

  componentDidUpdate(prevProps: IProps) {
    const { mapDetails: { id: prevMapId } } = prevProps;
    const { mapDetails: { id: mapId }, mapDetails } = this.props;
    if (prevMapId !== mapId) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ ...mapDetails });
    }
  }

  applyChanges = (): void => {
    const { ACTION_UPDATE_MAP_DETAILS_REQUESTED } = this.props;
    const updatedMapDetails = { ...this.state };

    ACTION_UPDATE_MAP_DETAILS_REQUESTED(updatedMapDetails);
  };

  handleChangeTabs = (event: Event, value: number): void => {
    this.numberTab = value;
    this.forceUpdate();
  };

  handleInputChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    this.setState({ [name]: value });
  };

  handleEditorChange = (text: string, { id: editorId }: { editorId: string }): void => {
    this.setState({ [editorId]: text });
  };

  handleSelectChange = (e: Event): void => {
    const { themesNames } = this.props;
    const { value, name } = (e.target: window.HTMLInputElement);

    const selectMenu = name === 'themeId' ? themesNames : ACCESS;
    const index = selectMenu.findIndex(style => style === value);
    const isControlled = name === 'securityType' && value === 'Controlled';

    if (isControlled) {
      this.setState({ [name]: index + 2 });

      return;
    }

    this.setState({ [name]: index + 1 });
  };

  handleCheckBoxChange = (e: Event, checkedVal: boolean, name: string): void => {
    this.setState({ [name]: checkedVal });
  };

  render() {
    const { classes, themesNames } = this.props;

    return (
      <Container>
        <Header>
          <Title>Map Details</Title>
          <Button
            color="primary"
            variant="contained"
            className={classes.button}
            onClick={this.applyChanges}
          >
            Save
          </Button>
        </Header>
        <Paper className={classes.paper}>
          <Tabs
            indicatorColor="primary"
            textColor="primary"
            value={this.numberTab}
            onChange={this.handleChangeTabs}
          >
            <Tab label="Basic Details" />
            <Tab label="Appearance" />
            <Tab label="Content Details" />
            <Tab label="Advanced Details" />
          </Tabs>
        </Paper>
        <ScrollingContainer>
          <TabContainer>
            {[
              <BasicDetails
                details={this.state}
                handleInputChange={this.handleInputChange}
                handleEditorChange={this.handleEditorChange}
                handleSelectChange={this.handleSelectChange}
              />,
              <Appearance
                details={this.state}
                themes={themesNames}
                handleSelectChange={this.handleSelectChange}
              />,
              <ContentDetails
                details={this.state}
                handleEditorChange={this.handleEditorChange}
                handleCheckBoxChange={this.handleCheckBoxChange}
              />,
              <AdvancedDetails
                details={this.state}
                handleCheckBoxChange={this.handleCheckBoxChange}
              />,
            ][this.numberTab]}
          </TabContainer>
        </ScrollingContainer>
      </Container>
    );
  }
}

const mapStateToProps = (
  { mapDetails: { themes = [], ...mapDetails } },
  { match: { params: { mapId: mapIdUrl } } },
) => {
  const themesNames = themes.map(theme => theme.name);

  return {
    themesNames,
    mapDetails,
    mapIdUrl,
  };
};

const mapDispatchToProps = dispatch => ({
  ACTION_GET_MAP_DETAILS_REQUESTED: (mapId: string) => {
    dispatch(mapDetailsActions.ACTION_GET_MAP_DETAILS_REQUESTED(mapId));
  },
  ACTION_UPDATE_MAP_DETAILS_REQUESTED: (mapDetails: MapDetails) => {
    dispatch(mapDetailsActions.ACTION_UPDATE_MAP_DETAILS_REQUESTED(mapDetails));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withStyles(styles)(AdvancedNodeEditor));
