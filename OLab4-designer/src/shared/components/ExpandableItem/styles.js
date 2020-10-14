import styled, { css } from 'styled-components';

export const ExpandableItemStyle = styled.p`
  font-family: "Roboto", "Helvetica", "Arial", sans-serif;
  margin: 0;
  color: rgba(0, 0, 0, .54);
  font-size: 0.75rem;
  font-weight: 400;

  ${({ isOpen }) => !isOpen && css`
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  `}

  ${({ isCollapsed }) => isCollapsed && css`
    cursor: pointer;
    &:hover {
      background: rgba(0, 0, 0, .05);
    }
  `}
`;

export default {
  ExpandableItemStyle,
};
