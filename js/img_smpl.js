smpl.dist = {
        x1: 10,            y1: 10,
        x2: smpl.width-10, y2: 10,
        x3: smpl.width-10, y3: smpl.height-10,
        x4: 10           , y4: smpl.height-10,
        radius: smpl.hradius,
        setDist: function(x1, y1, x2, y2, x3, y3, x4, y4 ){
          this.x1 = parseInt(x1);
          this.y1 = parseInt(y1);
          this.x2 = parseInt(x2);
          this.y2 = parseInt(y2);
          this.x3 = parseInt(x3);
          this.y3 = parseInt(y3);
          this.x4 = parseInt(x4);
          this.y4 = parseInt(y4);
        }, 
};

smpl.grid = {
  	strokew: 1,
  	dx:10,
  	dy:10
}