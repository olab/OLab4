// @flow
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Divider, Button } from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import NodeGridTable from './Table';
import CustomCtrlH from '../../shared/components/CustomCtrlH';
import CircularSpinnerWithText from '../../shared/components/CircularSpinnerWithText';

import * as nodeGridActions from '../../redux/nodeGrid/action';
import * as wholeMapActions from '../../middlewares/app/action';

import { isBoolean } from '../../helpers/dataTypes';
import { getNodesReduced, unEscapeNodes } from './utils';

import { KEY_S } from '../config';
import { FIELDS_TO_SEARCH } from './config';

import type { NodeGridProps as IProps, NodeGridState as IState, Node as NodeType } from './types';

import styles, { Wrapper, Header, Label } from '../CounterGrid/styles';

class NodeGrid extends Component<IProps, IState> {
  isModalOpen: boolean = false;

  constructor(props: IProps) {
    super(props);
    const {
      mapId, mapIdUrl, nodes, ACTION_GET_WHOLE_MAP_MIDDLEWARE,
    } = props;
    const isPageRefreshed = mapIdUrl && !mapId;

    if (isPageRefreshed) {
      ACTION_GET_WHOLE_MAP_MIDDLEWARE(mapIdUrl);
    }

    this.state = getNodesReduced(nodes);
  }

  componentDidMount() {
    document.addEventListener('keydown', this.handleKeyPressed);
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.handleKeyPressed);
  }

  componentDidUpdate(prevProps: IProps) {
    const { nodes: propsNodes } = this.props;
    const { nodes: prevPropsNodes } = prevProps;
    const shouldUpdateState = prevPropsNodes !== propsNodes;

    if (shouldUpdateState) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState(getNodesReduced(propsNodes));
    }
  }

  handleStateChange = (nodes: Array<Node>): void => {
    this.setState({ nodes });
  };

  handleModalShow = (isClosed: boolean | void): void => {
    this.isModalOpen = isBoolean(isClosed)
      ? isClosed
      : !this.isModalOpen;

    this.forceUpdate();
  };

  applyChanges = (): void => {
    const { nodes } = this.state;
    const { ACTION_UPDATE_NODE_GRID_REQUESTED } = this.props;

    ACTION_UPDATE_NODE_GRID_REQUESTED(unEscapeNodes(nodes));
  };

  handleKeyPressed = (e: KeyboardEvent): void => {
    const isSavingCombination = e.keyCode === KEY_S && (e.ctrlKey || e.metaKey);

    if (isSavingCombination) {
      e.preventDefault();
      this.applyChanges();
    }
  };

  render() {
    const { nodes } = this.state;
    const { classes, isFetching } = this.props;

    return (
      <Wrapper>
        <Header>
          <Label>Node grid</Label>
          <div>
            <Button
              color="default"
              variant="contained"
              className={classes.button}
              onClick={this.handleModalShow}
            >
              Find & Replace
            </Button>
            <Button
              color="primary"
              variant="contained"
              className={classes.button}
              onClick={this.applyChanges}
            >
              Save
            </Button>
          </div>
        </Header>
        <Divider />
        {
          isFetching
            ? <CircularSpinnerWithText large centered />
            : (
              <CustomCtrlH
                data={nodes}
                fields={FIELDS_TO_SEARCH}
                isShow={this.isModalOpen}
                onModalShow={this.handleModalShow}
                onStateChange={this.handleStateChange}
              >
                {(data, handleTableChange, handleSearchPopupClose) => (
                  <NodeGridTable
                    nodes={data}
                    onTableChange={handleTableChange}
                    onSearchPopupClose={handleSearchPopupClose}
                  />
                )}
              </CustomCtrlH>
            )
        }
      </Wrapper>
    );
  }
}

const mapStateToProps = (
  { map: { nodes, isFetching }, mapDetails: { id: mapId } },
  { match: { params: { mapId: mapIdUrl } } },
) => ({
  mapIdUrl: Number(mapIdUrl),
  nodes,
  isFetching,
  mapId,
});

const mapDispatchToProps = dispatch => ({
  ACTION_GET_WHOLE_MAP_MIDDLEWARE: (mapId: number) => {
    dispatch(wholeMapActions.ACTION_GET_WHOLE_MAP_MIDDLEWARE(mapId));
  },
  ACTION_UPDATE_NODE_GRID_REQUESTED: (nodes: Array<NodeType>) => {
    dispatch(nodeGridActions.ACTION_UPDATE_NODE_GRID_REQUESTED(nodes));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withStyles(styles)(NodeGrid));
