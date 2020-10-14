import styled from 'styled-components';

import { FOOTER_WIDTH, LINK_ICON_SIZE } from '../../config';
import { BLACK, WHITE, GREEN } from '../../../../../../shared/colors';

export const Wrapper = styled.div`
  position: absolute;
  display: flex;
  justify-content: flex-end;
  width: ${FOOTER_WIDTH}px;
  bottom: -16px;

  & > button:first-of-type {
    margin-right: 8px;
  }
`;

const styles = {
  fab: {
    color: BLACK,
    backgroundColor: WHITE,
    height: LINK_ICON_SIZE,
    width: LINK_ICON_SIZE,
    boxShadow: '0px 4px 8px rgba(36, 68, 106, 0.2)',
    minHeight: LINK_ICON_SIZE,
  },
  linkIcon: {
    backgroundColor: GREEN,
    cursor: 'inherit',
    '&:hover': {
      backgroundColor: GREEN,
    },
    '& path': {
      stroke: WHITE,
    },
  },
};

export default styles;
