// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { DragSource } from 'react-dnd';
import { Button } from '@material-ui/core';

import ChangeDirection from './ChangeDirection';
import Slider from '../../../shared/components/Slider';
import Switch from '../../../shared/components/Switch';
import ColorPicker from '../../../shared/components/ColorPicker';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import ScaleIcon from '../../../shared/assets/icons/cross.svg';

import type { Edge as LinkType } from '../../Constructor/Graph/Edge/types';
import type { ILinkEditorProps, ILinkEditorState } from './types';

import * as modalActions from '../../../redux/modals/action';
import * as mapActions from '../../../redux/map/action';

import { spec, collect } from '../utils';
import { LAYOUT_ENGINE } from '../../Constructor/config';
import { LINK_STYLES } from '../../config';
import { DND_CONTEXTS, MODALS_NAMES } from '../config';
import {
  THICKNESS_MIN, THICKNESS_MAX, THICKNESS_STEP, LINK_VARIANTS,
} from './config';

import {
  ModalWrapper, ModalHeader, ModalBody,
  ModalFooter, ArticleItem, ModalHeaderButton,
} from '../styles';
import {
  MenusArticle, SwitchArticle,
} from './styles';

class LinkEditor extends PureComponent<ILinkEditorProps, ILinkEditorState> {
  defaultLinkProps: LinkType | null;

  isLinkHasSibling: boolean = false;

  shouldUpdateVisual: boolean = false;

  constructor(props: ILinkEditorProps) {
    super(props);

    this.state = {
      ...props.link,
    };

    this.defaultLinkProps = {
      ...props.link,
    };

    this.isLinkHasSibling = this.checkIfLinkHasSibling(props.link);
  }

  // eslint-disable-next-line camelcase
  UNSAFE_componentWillReceiveProps(nextProps: ILinkEditorProps) {
    const { link } = nextProps;
    const { ACTION_UPDATE_EDGE, link: prevLink } = this.props;

    if (link.id !== prevLink.id) {
      const previousLink = {
        ...this.defaultLinkProps,
        isSelected: false,
      };

      ACTION_UPDATE_EDGE(previousLink, true);

      this.isLinkHasSibling = this.checkIfLinkHasSibling(link);

      this.defaultLinkProps = {
        ...link,
      };

      this.setState({
        ...link,
      });
    }
  }

  componentDidUpdate() {
    const { ACTION_UPDATE_EDGE } = this.props;

    if (this.shouldUpdateVisual) {
      ACTION_UPDATE_EDGE(this.state, true);

      this.shouldUpdateVisual = false;
    }
  }

  handleCloseModal = (): void => {
    const { ACTION_DESELECT_EDGE, ACTION_UPDATE_EDGE } = this.props;

    ACTION_UPDATE_EDGE(this.defaultLinkProps, true);
    ACTION_DESELECT_EDGE();
  }

  handleModalMove = (offsetX: number, offsetY: number): void => {
    const { ACTION_ADJUST_POSITION_MODAL } = this.props;
    ACTION_ADJUST_POSITION_MODAL(offsetX, offsetY);
  }

  handleSwitchChange = (e: Event, checked: boolean, name: string): void => {
    this.setState({ [name]: checked });
    this.shouldUpdateVisual = true;
  }

  handleInputChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);

    this.setState({ [name]: value });
    this.shouldUpdateVisual = true;
  }

  handleSelectChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);

    let menuItems = [];

    if (name === 'variant') {
      menuItems = LINK_VARIANTS;
    } else if (name === 'linkStyle') {
      menuItems = LINK_STYLES;
    }

    const index = menuItems.findIndex(item => item === value);

    this.setState({ [name]: index + 1 });
    this.shouldUpdateVisual = true;
  }

  handleSliderChange = (e: Event, thickness: number): void => {
    this.setState({ thickness });
    this.shouldUpdateVisual = true;
  };

  handleColorChange = (color: string): void => {
    this.setState({ color });
    this.shouldUpdateVisual = true;
  }

  handleDirectionChange = (): void => {
    const { source, target } = this.state;

    this.setState({
      source: target,
      target: source,
    });
    this.shouldUpdateVisual = true;
  }

  applyChanges = (): void => {
    const { ACTION_UPDATE_EDGE } = this.props;

    ACTION_UPDATE_EDGE(this.state);

    this.defaultLinkProps = {
      ...this.state,
    };
  }

  checkIfLinkHasSibling = ({ source: linkSource, target: linkTarget }: LinkType) => {
    const { links } = this.props;

    return links
      .some(({ source, target }) => source === linkTarget && target === linkSource);
  }

  render() {
    const {
      label, color, variant, thickness, linkStyle, isHidden, isFollowOnce,
    } = this.state;
    const {
      x, y, isDragging, connectDragSource, connectDragPreview, layoutEngine,
    } = this.props;

    const isShowChangeDirection = layoutEngine !== LAYOUT_ENGINE.NONE && !this.isLinkHasSibling;

    if (isDragging) {
      return null;
    }

    return (
      <ModalWrapper
        x={x}
        y={y}
        ref={instance => connectDragPreview(instance)}
      >
        <ModalHeader ref={instance => connectDragSource(instance)}>
          <h4>Link Editor</h4>
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
              name="label"
              label="Label"
              value={label}
              onChange={this.handleInputChange}
              fullWidth
            />
          </article>
          <MenusArticle>
            <OutlinedSelect
              label="Link Style"
              name="linkStyle"
              labelWidth={70}
              value={LINK_STYLES[linkStyle - 1]}
              values={LINK_STYLES}
              onChange={this.handleSelectChange}
              fullWidth
            />
            <OutlinedSelect
              label="Style"
              name="variant"
              labelWidth={40}
              value={LINK_VARIANTS[variant - 1]}
              values={LINK_VARIANTS}
              onChange={this.handleSelectChange}
              fullWidth
            />
          </MenusArticle>
          <article>
            <Slider
              label="Thickness"
              value={thickness}
              min={THICKNESS_MIN}
              max={THICKNESS_MAX}
              step={THICKNESS_STEP}
              onChange={this.handleSliderChange}
            />
          </article>
          <ArticleItem>
            <ColorPicker
              label="Color"
              color={color}
              onChange={this.handleColorChange}
            />
            {isShowChangeDirection && (
              <ChangeDirection
                label="Change Direction"
                title="Change Link Direction"
                size="small"
                onClick={this.handleDirectionChange}
              />
            )}
          </ArticleItem>
          <SwitchArticle>
            <Switch
              name="isHidden"
              label="Hidden"
              labelPlacement="start"
              checked={isHidden}
              onChange={this.handleSwitchChange}
            />
            <Switch
              name="isFollowOnce"
              label="Follow Once"
              labelPlacement="start"
              checked={isFollowOnce}
              onChange={this.handleSwitchChange}
            />
          </SwitchArticle>
        </ModalBody>
        <ModalFooter>
          <Button
            variant="contained"
            color="primary"
            onClick={this.applyChanges}
          >
            Save
          </Button>
        </ModalFooter>
      </ModalWrapper>
    );
  }
}

const mapStateToProps = ({ map, modals, constructor }) => ({
  ...modals[MODALS_NAMES.LINK_EDITOR_MODAL],
  links: map.edges,
  layoutEngine: constructor.layoutEngine,
});

const mapDispatchToProps = dispatch => ({
  ACTION_UPDATE_EDGE: (edge: LinkType, isVisualOnly: boolean = false) => {
    dispatch(mapActions.ACTION_UPDATE_EDGE(edge, isVisualOnly));
  },
  ACTION_DESELECT_EDGE: () => {
    dispatch(mapActions.ACTION_SELECT_EDGE(null));
  },
  ACTION_ADJUST_POSITION_MODAL: (offsetX: number, offsetY: number) => {
    dispatch(modalActions.ACTION_ADJUST_POSITION_MODAL(
      MODALS_NAMES.LINK_EDITOR_MODAL,
      offsetX,
      offsetY,
    ));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  DragSource(
    DND_CONTEXTS.VIEWPORT,
    spec,
    collect,
  )(LinkEditor),
);
