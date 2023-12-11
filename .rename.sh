find ./ -type f -print0 | xargs -0 perl -pi -e 's/Oneten_Cities/Oneten_Cities/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/oneten_cities/oneten_cities/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/oneten-cities/oneten-cities/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/starter_post_type/starter_post_type/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/Oneten Cities/Oneten Cities/g';
mv oneten-cities.php oneten-cities.php
#rm .rename.sh