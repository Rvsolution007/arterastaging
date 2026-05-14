const fabric = require('fabric').fabric;

const img1 = new fabric.Rect({ width: 100, height: 100, left: 0, top: 0, originX: 'center', originY: 'center' });
const img2 = new fabric.Rect({ width: 100, height: 100, left: 0, top: 0, originX: 'center', originY: 'center' });

const group = new fabric.Group([img1, img2], {
    left: 200,
    top: 200,
    originX: 'center',
    originY: 'center'
});

console.log('Group W/H:', group.width, group.height);
console.log('Img1 Left/Top after group:', img1.left, img1.top);
