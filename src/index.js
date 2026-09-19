import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
    InspectorControls,
    useBlockProps,
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    RangeControl, 
    ToggleControl,
    SelectControl,
    Button,
    Flex,
    FlexItem,
    FlexBlock,
    Spinner,
    Notice,
    BaseControl,
    ColorPalette,
    __experimentalBoxControl as BoxControl,
    __experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';

import './editor.scss';
import './style.scss';

registerBlockType('wm/newsticker', {
    title: __('Newsticker', 'wm-newsticker'),
    description: __('Displays announcements in a ticker format with customizable links.', 'wm-newsticker'),
    category: 'widgets',
    icon: 'megaphone',
    keywords: [__('news', 'wm-newsticker'), __('ticker', 'wm-newsticker'), __('announcements', 'wm-newsticker')],
    supports: {
        html: false,
        align: ['wide', 'full'],
    },
    attributes: {
        items: {
            type: 'array',
            default: [],
        },
        animationType: {
            type: 'string',
            default: 'scroll',
        },
        direction: {
            type: 'string',
            default: 'left',
        },
        speed: {
            type: 'number',
            default: 50,
        },
        pauseOnHover: {
            type: 'boolean',
            default: true,
        },
        backgroundColor: {
            type: 'string',
            default: '#1a1a2e',
        },
        textColor: {
            type: 'string',
            default: '#ffffff',
        },
        labelText: {
            type: 'string',
            default: '',
        },
        labelBackgroundColor: {
            type: 'string',
            default: '#e94560',
        },
        labelTextColor: {
            type: 'string',
            default: '#ffffff',
        },
        separator: {
            type: 'string',
            default: '•',
        },
        fontSize: {
            type: 'number',
            default: 14,
        },
        height: {
            type: 'number',
            default: 45,
        },
        isRTL: {
            type: 'boolean',
            default: false,
        },
        showControls: {
            type: 'boolean',
            default: false,
        },
        showPlayPause: {
            type: 'boolean',
            default: true,
        },
        showPrevNext: {
            type: 'boolean',
            default: true,
        },
        showProgress: {
            type: 'boolean',
            default: false,
        },
        controlsPosition: {
            type: 'string',
            default: 'right',
        },
        contentSource: {
            type: 'string',
            default: 'manual',
        },
        postType: {
            type: 'string',
            default: 'post',
        },
        postsCount: {
            type: 'number',
            default: 5,
        },
        categoryIds: {
            type: 'array',
            default: [],
        },
        tagIds: {
            type: 'array',
            default: [],
        },
        orderBy: {
            type: 'string',
            default: 'date',
        },
        order: {
            type: 'string',
            default: 'DESC',
        },
        showDate: {
            type: 'boolean',
            default: false,
        },
        dateFormat: {
            type: 'string',
            default: 'relative',
        },
        borderRadius: {
            type: 'object',
            default: {
                top: '0px',
                right: '0px',
                bottom: '0px',
                left: '0px',
            },
        },
        padding: {
            type: 'object',
            default: {
                top: '0px',
                right: '0px',
                bottom: '0px',
                left: '0px',
            },
        },
        margin: {
            type: 'object',
            default: {
                top: '0px',
                right: '0px',
                bottom: '0px',
                left: '0px',
            },
        },
        borderWidth: {
            type: 'string',
            default: '0px',
        },
        borderColor: {
            type: 'string',
            default: '#000000',
        },
        borderStyle: {
            type: 'string',
            default: 'solid',
        },
        boxShadow: {
            type: 'string',
            default: 'none',
        },
    },

    edit: function({ attributes, setAttributes }) {
        const {
            items,
            animationType,
            direction,
            speed,
            pauseOnHover,
            backgroundColor,
            textColor,
            labelText,
            labelBackgroundColor,
            labelTextColor,
            separator,
            fontSize,
            height,
            isRTL,
            showControls,
            showPlayPause,
            showPrevNext,
            showProgress,
            controlsPosition,
            contentSource,
            postType,
            postsCount,
            categoryIds,
            tagIds,
            orderBy,
            order,
            showDate,
            dateFormat,
            borderRadius,
            padding,
            margin,
            borderWidth,
            borderColor,
            borderStyle,
            boxShadow,
        } = attributes;

        // State for dynamic data
        const [postTypes, setPostTypes] = useState([]);
        const [categories, setCategories] = useState([]);
        const [tags, setTags] = useState([]);
        const [previewPosts, setPreviewPosts] = useState([]);
        const [isLoading, setIsLoading] = useState(false);

        // Fetch post types
        useEffect(() => {
            apiFetch({ path: '/wm-newsticker/v1/post-types' })
                .then((data) => setPostTypes(data))
                .catch(() => setPostTypes([{ value: 'post', label: 'Post' }]));
        }, []);

        // Fetch categories
        useEffect(() => {
            apiFetch({ path: '/wp/v2/categories?per_page=100' })
                .then((data) => setCategories(data.map(cat => ({ value: cat.id, label: cat.name }))))
                .catch(() => setCategories([]));
        }, []);

        // Fetch tags
        useEffect(() => {
            apiFetch({ path: '/wp/v2/tags?per_page=100' })
                .then((data) => setTags(data.map(tag => ({ value: tag.id, label: tag.name }))))
                .catch(() => setTags([]));
        }, []);

        // Fetch preview posts when dynamic source is selected
        useEffect(() => {
            if (contentSource !== 'posts') {
                setPreviewPosts([]);
                return;
            }

            setIsLoading(true);

            const controller = new AbortController();

            const validPostType = postTypes.find(pt => pt.value === postType) ? postType : 'post';
            const clampedCount = Math.min(20, Math.max(1, parseInt(postsCount, 10) || 5));

            const queryArgs = {
                per_page: clampedCount,
                orderby: orderBy,
                order: order.toLowerCase(),
            };

            if (categoryIds.length > 0 && validPostType === 'post') {
                queryArgs.categories = categoryIds.map(id => parseInt(id, 10)).join(',');
            }
            if (tagIds.length > 0 && validPostType === 'post') {
                queryArgs.tags = tagIds.map(id => parseInt(id, 10)).join(',');
            }

            const path = addQueryArgs(`/wp/v2/${encodeURIComponent(validPostType)}`, queryArgs);

            apiFetch({ path, signal: controller.signal })
                .then((data) => {
                    setPreviewPosts(data.map(post => ({
                        text: decodeEntities(post.title.rendered),
                        link: post.link,
                    })));
                    setIsLoading(false);
                })
                .catch((err) => {
                    if (err.name !== 'AbortError') {
                        setPreviewPosts([]);
                        setIsLoading(false);
                    }
                });

            return () => controller.abort();
        }, [contentSource, postType, postTypes, postsCount, categoryIds, tagIds, orderBy, order]);

        const defaultLabelText = __('NEWS', 'wm-newsticker');

        const blockProps = useBlockProps({
            className: 'wm-newsticker-editor',
        });

        const addItem = () => {
            const newItems = [...items, { text: '', link: '', newTab: false }];
            setAttributes({ items: newItems });
        };

        const removeItem = (index) => {
            const newItems = items.filter((_, i) => i !== index);
            setAttributes({ items: newItems });
        };

        const updateItem = (index, field, value) => {
            const allowedFields = ['text', 'link', 'newTab'];
            if (!allowedFields.includes(field)) return;
            const newItems = items.map((item, i) => {
                if (i === index) {
                    return { ...item, [field]: value };
                }
                return item;
            });
            setAttributes({ items: newItems });
        };

        const moveItem = (index, delta) => {
            const newIndex = index + delta;
            if (newIndex < 0 || newIndex >= items.length) return;
            
            const newItems = [...items];
            [newItems[index], newItems[newIndex]] = [newItems[newIndex], newItems[index]];
            setAttributes({ items: newItems });
        };

        const displayLabel = labelText || defaultLabelText;

        const animationOptions = [
            { label: __('Scroll (Marquee)', 'wm-newsticker'), value: 'scroll' },
            { label: __('Fade', 'wm-newsticker'), value: 'fade' },
            { label: __('Slide', 'wm-newsticker'), value: 'slide' },
            { label: __('Typing', 'wm-newsticker'), value: 'typing' },
        ];

        const directionOptions = [
            { label: __('Left', 'wm-newsticker'), value: 'left' },
            { label: __('Right', 'wm-newsticker'), value: 'right' },
            { label: __('Up', 'wm-newsticker'), value: 'up' },
            { label: __('Down', 'wm-newsticker'), value: 'down' },
        ];

        const controlsPositionOptions = [
            { label: __('Left', 'wm-newsticker'), value: 'left' },
            { label: __('Right', 'wm-newsticker'), value: 'right' },
        ];

        const contentSourceOptions = [
            { label: __('Manual announcements', 'wm-newsticker'), value: 'manual' },
            { label: __('Recent posts', 'wm-newsticker'), value: 'posts' },
        ];

        const orderByOptions = [
            { label: __('Date', 'wm-newsticker'), value: 'date' },
            { label: __('Title', 'wm-newsticker'), value: 'title' },
            { label: __('Last modified', 'wm-newsticker'), value: 'modified' },
            { label: __('Random', 'wm-newsticker'), value: 'rand' },
            { label: __('Comment count', 'wm-newsticker'), value: 'comment_count' },
        ];

        const orderOptions = [
            { label: __('Descending (newest first)', 'wm-newsticker'), value: 'DESC' },
            { label: __('Ascending (oldest first)', 'wm-newsticker'), value: 'ASC' },
        ];

        const dateFormatOptions = [
            { label: __('Relative (e.g., 2 hours ago)', 'wm-newsticker'), value: 'relative' },
            { label: __('Absolute (site format)', 'wm-newsticker'), value: 'absolute' },
        ];

        // Get display items for preview
        const displayItems = contentSource === 'posts' ? previewPosts : items;

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Animation', 'wm-newsticker')} initialOpen={true}>
                        <SelectControl
                            label={__('Animation type', 'wm-newsticker')}
                            value={animationType}
                            options={animationOptions}
                            onChange={(value) => setAttributes({ animationType: value })}
                            help={__('Choose how announcements are displayed', 'wm-newsticker')}
                        />
                        <SelectControl
                            label={__('Direction', 'wm-newsticker')}
                            value={direction}
                            options={directionOptions}
                            onChange={(value) => setAttributes({ direction: value })}
                        />
                        <RangeControl
                            label={__('Speed', 'wm-newsticker')}
                            value={speed}
                            onChange={(value) => setAttributes({ speed: value })}
                            min={10}
                            max={100}
                            help={__('Higher value = faster', 'wm-newsticker')}
                        />
                        <ToggleControl
                            label={__('Pause on hover', 'wm-newsticker')}
                            checked={pauseOnHover}
                            onChange={(value) => setAttributes({ pauseOnHover: value })}
                        />
                        <ToggleControl
                            label={__('RTL (Right-to-Left)', 'wm-newsticker')}
                            checked={isRTL}
                            onChange={(value) => setAttributes({ isRTL: value })}
                            help={__('Enable for Arabic, Hebrew, etc.', 'wm-newsticker')}
                        />
                    </PanelBody>

                    <PanelBody title={__('Controls', 'wm-newsticker')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show controls', 'wm-newsticker')}
                            checked={showControls}
                            onChange={(value) => setAttributes({ showControls: value })}
                            help={__('Display Play/Pause and Prev/Next buttons', 'wm-newsticker')}
                        />
                        {showControls && (
                            <>
                                <SelectControl
                                    label={__('Controls position', 'wm-newsticker')}
                                    value={controlsPosition}
                                    options={controlsPositionOptions}
                                    onChange={(value) => setAttributes({ controlsPosition: value })}
                                />
                                <ToggleControl
                                    label={__('Play/Pause button', 'wm-newsticker')}
                                    checked={showPlayPause}
                                    onChange={(value) => setAttributes({ showPlayPause: value })}
                                />
                                <ToggleControl
                                    label={__('Previous/Next buttons', 'wm-newsticker')}
                                    checked={showPrevNext}
                                    onChange={(value) => setAttributes({ showPrevNext: value })}
                                    help={animationType === 'scroll' ? __('Only works with Fade/Slide/Typing animations', 'wm-newsticker') : ''}
                                />
                                <ToggleControl
                                    label={__('Progress indicator', 'wm-newsticker')}
                                    checked={showProgress}
                                    onChange={(value) => setAttributes({ showProgress: value })}
                                    help={__('Shows a progress bar at the bottom', 'wm-newsticker')}
                                />
                            </>
                        )}
                    </PanelBody>

                    <PanelBody title={__('General Settings', 'wm-newsticker')} initialOpen={false}>
                        <TextControl
                            label={__('Label text', 'wm-newsticker')}
                            value={labelText}
                            onChange={(value) => setAttributes({ labelText: value })}
                            placeholder={defaultLabelText}
                            help={__('Leave empty to hide the label on the frontend', 'wm-newsticker')}
                        />
                        {animationType === 'scroll' && (
                            <TextControl
                                label={__('Separator', 'wm-newsticker')}
                                value={separator}
                                onChange={(value) => setAttributes({ separator: value })}
                            />
                        )}
                        <RangeControl
                            label={__('Font size (px)', 'wm-newsticker')}
                            value={fontSize}
                            onChange={(value) => setAttributes({ fontSize: value })}
                            min={10}
                            max={72}
                        />
                        <RangeControl
                            label={__('Height (px)', 'wm-newsticker')}
                            value={height}
                            onChange={(value) => setAttributes({ height: value })}
                            min={20}
                            max={200}
                        />
                    </PanelBody>

                    <PanelBody title={__('Colors', 'wm-newsticker')} initialOpen={false}>
                        <div className="wm-color-controls">
                            <BaseControl label={__('Ticker background', 'wm-newsticker')} className="wm-color-control">
                                <div className="wm-color-input-row">
                                    <input
                                        type="color"
                                        value={backgroundColor}
                                        onChange={(e) => setAttributes({ backgroundColor: e.target.value })}
                                    />
                                    <TextControl
                                        value={backgroundColor}
                                        onChange={(value) => setAttributes({ backgroundColor: value })}
                                        className="wm-color-text"
                                    />
                                </div>
                            </BaseControl>
                            <BaseControl label={__('Text color', 'wm-newsticker')} className="wm-color-control">
                                <div className="wm-color-input-row">
                                    <input
                                        type="color"
                                        value={textColor}
                                        onChange={(e) => setAttributes({ textColor: e.target.value })}
                                    />
                                    <TextControl
                                        value={textColor}
                                        onChange={(value) => setAttributes({ textColor: value })}
                                        className="wm-color-text"
                                    />
                                </div>
                            </BaseControl>
                            <BaseControl label={__('Label background', 'wm-newsticker')} className="wm-color-control">
                                <div className="wm-color-input-row">
                                    <input
                                        type="color"
                                        value={labelBackgroundColor}
                                        onChange={(e) => setAttributes({ labelBackgroundColor: e.target.value })}
                                    />
                                    <TextControl
                                        value={labelBackgroundColor}
                                        onChange={(value) => setAttributes({ labelBackgroundColor: value })}
                                        className="wm-color-text"
                                    />
                                </div>
                            </BaseControl>
                            <BaseControl label={__('Label text', 'wm-newsticker')} className="wm-color-control">
                                <div className="wm-color-input-row">
                                    <input
                                        type="color"
                                        value={labelTextColor}
                                        onChange={(e) => setAttributes({ labelTextColor: e.target.value })}
                                    />
                                    <TextControl
                                        value={labelTextColor}
                                        onChange={(value) => setAttributes({ labelTextColor: value })}
                                        className="wm-color-text"
                                    />
                                </div>
                            </BaseControl>
                            <BaseControl label={__('Border color', 'wm-newsticker')} className="wm-color-control">
                                <div className="wm-color-input-row">
                                    <input
                                        type="color"
                                        value={borderColor}
                                        onChange={(e) => setAttributes({ borderColor: e.target.value })}
                                    />
                                    <TextControl
                                        value={borderColor}
                                        onChange={(value) => setAttributes({ borderColor: value })}
                                        className="wm-color-text"
                                    />
                                </div>
                            </BaseControl>
                        </div>
                    </PanelBody>

                    <PanelBody title={__('Spacing & Borders', 'wm-newsticker')} initialOpen={false}>
                        <BoxControl
                            label={__('Padding', 'wm-newsticker')}
                            values={padding}
                            onChange={(value) => setAttributes({ padding: value })}
                            units={[
                                { value: 'px', label: 'px' },
                                { value: 'em', label: 'em' },
                                { value: 'rem', label: 'rem' },
                            ]}
                        />
                        <BoxControl
                            label={__('Margin', 'wm-newsticker')}
                            values={margin}
                            onChange={(value) => setAttributes({ margin: value })}
                            units={[
                                { value: 'px', label: 'px' },
                                { value: 'em', label: 'em' },
                                { value: 'rem', label: 'rem' },
                            ]}
                        />
                        <BoxControl
                            label={__('Border Radius', 'wm-newsticker')}
                            values={borderRadius}
                            onChange={(value) => setAttributes({ borderRadius: value })}
                            units={[
                                { value: 'px', label: 'px' },
                                { value: '%', label: '%' },
                            ]}
                        />
                        <Flex gap={2}>
                            <FlexBlock>
                                <UnitControl
                                    label={__('Border Width', 'wm-newsticker')}
                                    value={borderWidth}
                                    onChange={(value) => setAttributes({ borderWidth: value })}
                                    units={[
                                        { value: 'px', label: 'px' },
                                    ]}
                                />
                            </FlexBlock>
                            <FlexBlock>
                                <SelectControl
                                    label={__('Border Style', 'wm-newsticker')}
                                    value={borderStyle}
                                    options={[
                                        { label: __('Solid', 'wm-newsticker'), value: 'solid' },
                                        { label: __('Dashed', 'wm-newsticker'), value: 'dashed' },
                                        { label: __('Dotted', 'wm-newsticker'), value: 'dotted' },
                                        { label: __('None', 'wm-newsticker'), value: 'none' },
                                    ]}
                                    onChange={(value) => setAttributes({ borderStyle: value })}
                                />
                            </FlexBlock>
                        </Flex>
                        <SelectControl
                            label={__('Box Shadow', 'wm-newsticker')}
                            value={boxShadow}
                            options={[
                                { label: __('None', 'wm-newsticker'), value: 'none' },
                                { label: __('Small', 'wm-newsticker'), value: '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)' },
                                { label: __('Medium', 'wm-newsticker'), value: '0 3px 6px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.12)' },
                                { label: __('Large', 'wm-newsticker'), value: '0 10px 20px rgba(0,0,0,0.15), 0 3px 6px rgba(0,0,0,0.10)' },
                                { label: __('Inset', 'wm-newsticker'), value: 'inset 0 2px 4px rgba(0,0,0,0.1)' },
                            ]}
                            onChange={(value) => setAttributes({ boxShadow: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Content Source', 'wm-newsticker')} initialOpen={true}>
                        <SelectControl
                            label={__('Source', 'wm-newsticker')}
                            value={contentSource}
                            options={contentSourceOptions}
                            onChange={(value) => setAttributes({ contentSource: value })}
                        />

                        {contentSource === 'posts' && (
                            <>
                                <SelectControl
                                    label={__('Post type', 'wm-newsticker')}
                                    value={postType}
                                    options={postTypes.length > 0 ? postTypes : [{ value: 'post', label: 'Post' }]}
                                    onChange={(value) => setAttributes({ postType: value })}
                                />
                                <RangeControl
                                    label={__('Number of posts', 'wm-newsticker')}
                                    value={postsCount}
                                    onChange={(value) => setAttributes({ postsCount: value })}
                                    min={1}
                                    max={20}
                                />
                                {postType === 'post' && categories.length > 0 && (
                                    <SelectControl
                                        label={__('Filter by category', 'wm-newsticker')}
                                        value={categoryIds.length > 0 ? categoryIds[0] : ''}
                                        options={[
                                            { value: '', label: __('All categories', 'wm-newsticker') },
                                            ...categories
                                        ]}
                                        onChange={(value) => setAttributes({ categoryIds: value ? [parseInt(value)] : [] })}
                                        help={__('Select a category to filter posts', 'wm-newsticker')}
                                    />
                                )}
                                {postType === 'post' && tags.length > 0 && (
                                    <SelectControl
                                        label={__('Filter by tag', 'wm-newsticker')}
                                        value={tagIds.length > 0 ? tagIds[0] : ''}
                                        options={[
                                            { value: '', label: __('All tags', 'wm-newsticker') },
                                            ...tags
                                        ]}
                                        onChange={(value) => setAttributes({ tagIds: value ? [parseInt(value)] : [] })}
                                    />
                                )}
                                <SelectControl
                                    label={__('Order by', 'wm-newsticker')}
                                    value={orderBy}
                                    options={orderByOptions}
                                    onChange={(value) => setAttributes({ orderBy: value })}
                                />
                                <SelectControl
                                    label={__('Order', 'wm-newsticker')}
                                    value={order}
                                    options={orderOptions}
                                    onChange={(value) => setAttributes({ order: value })}
                                />
                                <ToggleControl
                                    label={__('Show date', 'wm-newsticker')}
                                    checked={showDate}
                                    onChange={(value) => setAttributes({ showDate: value })}
                                />
                                {showDate && (
                                    <SelectControl
                                        label={__('Date format', 'wm-newsticker')}
                                        value={dateFormat}
                                        options={dateFormatOptions}
                                        onChange={(value) => setAttributes({ dateFormat: value })}
                                    />
                                )}
                                {isLoading && (
                                    <div className="wm-newsticker-loading">
                                        <Spinner /> {__('Loading posts...', 'wm-newsticker')}
                                    </div>
                                )}
                                {!isLoading && previewPosts.length > 0 && (
                                    <Notice status="info" isDismissible={false}>
                                        {__('Preview:', 'wm-newsticker')} {previewPosts.length} {__('posts found', 'wm-newsticker')}
                                    </Notice>
                                )}
                                {!isLoading && previewPosts.length === 0 && contentSource === 'posts' && (
                                    <Notice status="warning" isDismissible={false}>
                                        {__('No posts found with current filters', 'wm-newsticker')}
                                    </Notice>
                                )}
                            </>
                        )}

                        {contentSource === 'manual' && (
                            <>
                                {items.map((item, index) => (
                            <div key={index} className="wm-newsticker-item-panel">
                                <Flex align="flex-start">
                                    <FlexBlock>
                                        <TextControl
                                            label={__('Announcement text', 'wm-newsticker') + ` #${index + 1}`}
                                            value={item.text}
                                            onChange={(value) => updateItem(index, 'text', value)}
                                        />
                                        <TextControl
                                            label={__('Link (optional)', 'wm-newsticker')}
                                            value={item.link}
                                            onChange={(value) => updateItem(index, 'link', value)}
                                            type="url"
                                        />
                                        <ToggleControl
                                            label={__('Open in new tab', 'wm-newsticker')}
                                            checked={item.newTab || false}
                                            onChange={(value) => updateItem(index, 'newTab', value)}
                                        />
                                    </FlexBlock>
                                    <FlexItem>
                                        <div className="wm-newsticker-item-controls">
                                            <Button
                                                icon="arrow-up"
                                                onClick={() => moveItem(index, -1)}
                                                disabled={index === 0}
                                                size="small"
                                                label={__('Move up', 'wm-newsticker')}
                                            />
                                            <Button
                                                icon="arrow-down"
                                                onClick={() => moveItem(index, 1)}
                                                disabled={index === items.length - 1}
                                                size="small"
                                                label={__('Move down', 'wm-newsticker')}
                                            />
                                            <Button
                                                icon="trash"
                                                onClick={() => removeItem(index)}
                                                isDestructive
                                                size="small"
                                                label={__('Delete', 'wm-newsticker')}
                                            />
                                        </div>
                                    </FlexItem>
                                </Flex>
                            </div>
                        ))}
                        <Button
                            variant="primary"
                            onClick={addItem}
                            icon="plus"
                            style={{ marginTop: '10px' }}
                        >
                            {__('Add announcement', 'wm-newsticker')}
                        </Button>
                            </>
                        )}
                    </PanelBody>

                    <PanelBody title={__('About', 'wm-newsticker')} initialOpen={false}>
                        <div className="wm-newsticker-credits">
                            <p><strong>WM Newsticker</strong></p>
                            <p className="wm-newsticker-version">Version 1.4.6</p>
                        </div>
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div 
                        className={`wm-newsticker-preview wm-animation-${animationType}${isRTL ? ' wm-rtl' : ''}${showControls ? ' wm-has-controls wm-controls-' + controlsPosition : ''}`}
                        style={{ 
                            backgroundColor: backgroundColor,
                            height: `${height}px`
                        }}
                    >
                        {labelText && (
                            <div
                                className="wm-newsticker-label"
                                style={{
                                    backgroundColor: labelBackgroundColor,
                                    color: labelTextColor,
                                    fontSize: `${fontSize}px`,
                                }}
                            >
                                {displayLabel}
                            </div>
                        )}

                        {showControls && controlsPosition === 'left' && (
                            <div className="wm-newsticker-controls-preview" style={{ color: textColor }}>
                                {showPrevNext && <span className="wm-ctrl-icon">◀</span>}
                                {showPlayPause && <span className="wm-ctrl-icon">⏸</span>}
                                {showPrevNext && <span className="wm-ctrl-icon">▶</span>}
                            </div>
                        )}

                        <div className="wm-newsticker-content">
                            {showProgress && (
                                <div className="wm-newsticker-progress-preview">
                                    <div className="wm-newsticker-progress-bar-preview"></div>
                                </div>
                            )}
                            {displayItems.length === 0 ? (
                                <span 
                                    className="wm-newsticker-placeholder"
                                    style={{ color: textColor, fontSize: `${fontSize}px` }}
                                >
                                    {contentSource === 'posts' 
                                        ? (isLoading ? __('Loading...', 'wm-newsticker') : __('No posts found', 'wm-newsticker'))
                                        : __('Add announcements in the sidebar →', 'wm-newsticker')
                                    }
                                </span>
                            ) : (
                                <div 
                                    className="wm-newsticker-track"
                                    style={{ color: textColor, fontSize: `${fontSize}px` }}
                                >
                                    {displayItems.map((item, index) => (
                                        <span key={index}>
                                            {index > 0 && animationType === 'scroll' && (
                                                <span className="wm-newsticker-separator">
                                                    {` ${separator} `}
                                                </span>
                                            )}
                                            <span className={item.link ? 'has-link' : ''}>
                                                {item.text || __('(No text)', 'wm-newsticker')}
                                            </span>
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>

                        {showControls && controlsPosition === 'right' && (
                            <div className="wm-newsticker-controls-preview" style={{ color: textColor }}>
                                {showPrevNext && <span className="wm-ctrl-icon">◀</span>}
                                {showPlayPause && <span className="wm-ctrl-icon">⏸</span>}
                                {showPrevNext && <span className="wm-ctrl-icon">▶</span>}
                            </div>
                        )}

                        <div className="wm-newsticker-animation-badge">
                            {contentSource === 'posts' ? 'POSTS' : animationType.toUpperCase()}
                        </div>
                    </div>
                </div>
            </>
        );
    },

    save: function() {
        return null;
    },
});
