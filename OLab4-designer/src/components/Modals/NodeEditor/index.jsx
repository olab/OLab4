// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { DragSource } from 'react-dnd';
import { withStyles } from '@material-ui/core/styles';
import { Button } from '@material-ui/core';
import isEqual from 'lodash.isequal';

import Switch from '../../../shared/components/Switch';
import ScaleIcon from '../../../shared/assets/icons/cross.svg';
import TextEditor from '../../../shared/components/TextEditor';
import ColorPicker from '../../../shared/components/ColorPicker';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';

import { spec, collect } from '../utils';
import { redirectToPlayer } from '../../utils';

import * as modalActions from '../../../redux/modals/action';
import * as mapActions from '../../../redux/map/action';
import * as wholeMapActions from '../../../middlewares/app/action';

import { EDITOR_OPTIONS } from './config';
import { LINK_STYLES, KEY_S } from '../../config';
import { DND_CONTEXTS, MODALS_NAMES } from '../config';

import type { INodeEditorProps, INodeEditorState } from './types';
import type { Node as NodeType } from '../../Constructor/Graph/Node/types';

import {
  NodeEditorWrapper, ModalHeader, ModalBody,
  ModalFooter, ArticleItem, ModalHeaderButton,
} from '../styles';
import styles, { Triangle } from './styles';

class NodeEditor extends PureComponent<INodeEditorProps, INodeEditorState> {
  constructor(props: INodeEditorProps) {
    super(props);
    this.state = { ...props.node };
  }

  componentDidUpdate(prevProps: INodeEditorProps) {
    const {
      node,
      node: {
        id, title, linkStyle, color, isVisitOnce, text, ...restNode
      },
    } = this.props;
    const {
      id: idPrev,
      title: titlePrev,
      linkStyle: linkStylePrev,
      color: colorPrev,
      isVisitOnce: isVisitOncePrev,
      text: textPrev,
      ...restNodePrev
    } = this.state;

    if (id !== idPrev || prevProps.node !== node) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ ...node });
    }

    if (!isEqual(restNode, restNodePrev)) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ ...restNode });
    }
  }

  handleCloseModal = (): void => {
    const { node: { id: nodeId }, ACTION_UNFOCUS_NODE } = this.props;
    ACTION_UNFOCUS_NODE(nodeId);
  }

  handleInputChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    this.setState({ [name]: value });
  }

  handleTextChange = (text: string): void => {
    this.setState({ text });
  }

  handleStyleChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const index = LINK_STYLES.findIndex(style => style === value);
    this.setState({ [name]: index + 1 });
  }

  handleColorChange = (color: string): void => {
    this.setState({ color });
  }

  handleVisitOnceChange = (e: Event): void => {
    const { checked: isVisitOnce } = (e.target: window.HTMLInputElement);
    this.setState({ isVisitOnce });
  }

  handleModalMove = (offsetX: number, offsetY: number): void => {
    const { ACTION_ADJUST_POSITION_MODAL } = this.props;
    ACTION_ADJUST_POSITION_MODAL(offsetX, offsetY);
  }

  toggleScopedObjectModal = (): void => {
    const { ACTION_TOGGLE_SO_PICKER_MODAL } = this.props;
    ACTION_TOGGLE_SO_PICKER_MODAL();
  }

  applyChanges = (): void => {
    const { ACTION_UPDATE_NODE } = this.props;
    ACTION_UPDATE_NODE(this.state, true);
  }

  deleteNode = (): void => {
    const {
      mapId,
      node: { id: nodeId, type: nodeType },
      ACTION_DELETE_NODE_MIDDLEWARE,
    } = this.props;

    ACTION_DELETE_NODE_MIDDLEWARE(mapId, nodeId, nodeType);
  }

  handleKeyPressed = (e: KeyboardEvent): void => {
    const isSavingCombination = e.keyCode === KEY_S && (e.ctrlKey || e.metaKey);

    if (isSavingCombination) {
      e.preventDefault();
      this.applyChanges();
    }
  }

  handleModalRef = (instance) => {
    const { connectDragPreview } = this.props;
    connectDragPreview(instance);

    if (instance) {
      instance.focus();
    }
  }

  render() {
    const {
      color, title, isVisitOnce, linkStyle, text,
    } = this.state;
    const {
      x, y, isDragging, connectDragSource, classes, node: { id: nodeId }, mapId, isShow,
    } = this.props;

    if (isDragging) {
      return null;
    }

    return (
      <NodeEditorWrapper
        ref={this.handleModalRef}
        onKeyDown={this.handleKeyPressed}
        isShow={isShow}
        x={x}
        y={y}
      >
        <ModalHeader ref={instance => connectDragSource(instance)}>
          <h4>Node Editor</h4>
          <Button
            size="small"
            variant="outlined"
            color="primary"
            className={classes.button}
            component={Link}
            to={`/${mapId}/${nodeId}/ane`}
            target="_blank"
          >
            Advanced
          </Button>
          <Button
            size="small"
            variant="outlined"
            color="primary"
            className={classes.button}
            onClick={this.toggleScopedObjectModal}
          >
            Object Picker
          </Button>
          <ModalHeaderButton
            type="button"
            onClick={this.handleCloseModal}
          >
            <ScaleIcon />
          </ModalHeaderButton>
        </ModalHeader>
        <ModalBody>
          <article>
            <OutlinedInput
              name="title"
              label="Title"
              value={title}
              onChange={this.handleInputChange}
              fullWidth
            />
          </article>
          <ArticleItem>
            <OutlinedSelect
              label="Links Style"
              name="linkStyle"
              labelWidth={80}
              value={LINK_STYLES[linkStyle - 1]}
              values={LINK_STYLES}
              onChange={this.handleStyleChange}
              limitMaxWidth
            />
            <ColorPicker
              label="Color"
              color={color}
              onChange={this.handleColorChange}
            />
            <Switch
              label="Visit Once"
              labelPlacement="start"
              checked={isVisitOnce}
              onChange={this.handleVisitOnceChange}
            />
          </ArticleItem>
          <article>
            <TextEditor
              text={text}
              width={440}
              height={300}
              handleEditorChange={this.handleTextChange}
              editorOptions={EDITOR_OPTIONS}
              handleKeyDown={this.handleKeyPressed}
            />
          </article>
        </ModalBody>
        <ModalFooter>
          <Button
            variant="contained"
            color="default"
            onClick={this.deleteNode}
            className={classes.deleteButton}
          >
            Delete
          </Button>
          <Button
            variant="contained"
            color="default"
            className={classes.previewButton}
            onClick={redirectToPlayer(mapId, nodeId)}
          >
            <Triangle>&#9658;</Triangle>
            Preview
          </Button>
          <Button
            variant="contained"
            color="primary"
            onClick={this.applyChanges}
          >
            Save
          </Button>
        </ModalFooter>
      </NodeEditorWrapper>
    );
  }
}

const mapStateToProps = ({ modals, mapDetails }) => ({
  ...modals[MODALS_NAMES.NODE_EDITOR_MODAL],
  mapId: mapDetails.id,
});

const mapDispatchToProps = dispatch => ({
  ACTION_UPDATE_NODE: (nodeData: NodeType, isShowNotification: boolean) => {
    dispatch(mapActions.ACTION_UPDATE_NODE(nodeData, isShowNotification));
  },
  ACTION_UNFOCUS_NODE: (nodeId: number) => {
    dispatch(mapActions.ACTION_UNFOCUS_NODE(nodeId));
  },
  ACTION_ADJUST_POSITION_MODAL: (offsetX: number, offsetY: number) => {
    dispatch(modalActions.ACTION_ADJUST_POSITION_MODAL(
      MODALS_NAMES.NODE_EDITOR_MODAL,
      offsetX,
      offsetY,
    ));
  },
  ACTION_TOGGLE_SO_PICKER_MODAL: () => {
    dispatch(modalActions.ACTION_TOGGLE_MODAL(
      MODALS_NAMES.SO_PICKER_MODAL,
    ));
  },
  ACTION_DELETE_NODE_MIDDLEWARE: (mapId: number, nodeId: number, nodeType: number) => {
    dispatch(wholeMapActions.ACTION_DELETE_NODE_MIDDLEWARE(mapId, nodeId, nodeType));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  withStyles(styles)(
    DragSource(
      DND_CONTEXTS.VIEWPORT,
      spec,
      collect,
    )(NodeEditor),
  ),
);
