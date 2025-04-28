import simplekml

# Read coordinates from poly.txt
coords = []
with open('one.txt', 'r') as file:
    for line in file:
        lon, lat = map(float, line.strip().split())
        coords.append((lon, lat))

# Close the polygon
coords.append(coords[0])

# Create KML
kml = simplekml.Kml()
pol = kml.newpolygon(name="Nepal Polygon")
pol.outerboundaryis.coords = coords
pol.style.linestyle.color = simplekml.Color.red
pol.style.linestyle.width = 2
pol.style.polystyle.fill = 1
pol.style.polystyle.color = simplekml.Color.changealphaint(100, simplekml.Color.red)

# Save KML
kml.save('polygon.kml')
print("KML file created: polygon.kml")