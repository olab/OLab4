// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { Divider, Button } from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';

import CounterGridTable from './Table';
import CircularSpinnerWithText from '../../shared/components/CircularSpinnerWithText';

import * as counterGridActions from '../../redux/counterGrid/action';

import { parseSendingData } from './utils';

import type { CounterGridProps } from './types';
import type { Counter as CounterType } from '../../redux/counterGrid/types';

import styles, { Wrapper, Header, Label } from './styles';

class CounterGrid extends PureComponent<CounterGridProps> {
  constructor(props: CounterGridProps) {
    super(props);
    const { mapId, ACTION_GET_COUNTER_GRID_REQUESTED } = props;
    ACTION_GET_COUNTER_GRID_REQUESTED(mapId);

    this.tableRef = React.createRef();
  }

  componentWillUnmount() {
    const { ACTION_CLEAR } = this.props;
    ACTION_CLEAR();
  }

  handleSaveButtonClick = (): void => {
    const { mapId, ACTION_UPDATE_COUNTER_GRID_REQUESTED } = this.props;
    const childComponent = this.tableRef.current;

    if (childComponent) {
      const { state: { countersValues } } = childComponent;
      const counterActions = parseSendingData(countersValues);

      ACTION_UPDATE_COUNTER_GRID_REQUESTED(mapId, counterActions);
    }
  }

  render() {
    const {
      counters, nodes, actions, classes, isFetching,
    } = this.props;
    const isFirstLoading = isFetching && !nodes.length && !counters.length;

    return (
      <Wrapper>
        <Header>
          <Label>Counter grid</Label>
          <Button
            color="primary"
            variant="contained"
            className={classes.button}
            onClick={this.handleSaveButtonClick}
          >
            Save changes
          </Button>
        </Header>
        <Divider />
        {isFirstLoading ? (
          <CircularSpinnerWithText large centered />
        ) : (
          <CounterGridTable
            innerRef={this.tableRef}
            counters={counters}
            nodes={nodes}
            actions={actions}
          />
        )}
      </Wrapper>
    );
  }
}

const mapStateToProps = ({
  counterGrid: {
    counters, nodes, actions, isFetching,
  },
}, { match: { params: { mapId } } }) => ({
  counters, nodes, actions, isFetching, mapId,
});

const mapDispatchToProps = dispatch => ({
  ACTION_GET_COUNTER_GRID_REQUESTED: (mapId: string) => {
    dispatch(counterGridActions.ACTION_GET_COUNTER_GRID_REQUESTED(mapId));
  },
  ACTION_UPDATE_COUNTER_GRID_REQUESTED: (mapId: string, counterActions: CounterType) => {
    dispatch(
      counterGridActions.ACTION_UPDATE_COUNTER_GRID_REQUESTED(mapId, counterActions),
    );
  },
  ACTION_CLEAR: () => {
    dispatch(counterGridActions.COUNTER_GRID_ACTION_ACTIONS_CLEAR());
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withStyles(styles)(CounterGrid));
